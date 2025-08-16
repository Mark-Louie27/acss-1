<?php
// File: controllers/FacultyController.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/AuthService.php';

class FacultyController
{
    private $db;
    private $authService;

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
        $this->authService = new AuthService($this->db);
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
        error_log("profile: Starting profile method for user_id: " . ($_SESSION['user_id'] ?? 'unknown'));
        try {
            if (!$this->authService->isLoggedIn()) {
                error_log("profile: User not logged in");
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to view your profile'];
                header('Location: /login');
                exit;
            }

            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                error_log("profile: No faculty profile found for user_id: $userId");
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Faculty profile not found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            $csrf_token = $this->authService->generateCsrfToken();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    error_log("profile: Invalid CSRF token for user_id: $userId");
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token'];
                    header('Location: /faculty/profile');
                    exit;
                }

                if (isset($_POST['update_profile'])) {
                    $firstName = trim($_POST['first_name'] ?? '');
                    $lastName = trim($_POST['last_name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $phone = trim($_POST['phone'] ?? null);
                    $classification = trim($_POST['classification'] ?? '') ?: null;
                    $academic_rank = trim($_POST['academic_rank'] ?? '') ?: null;
                    $employment_type = trim($_POST['employment_type'] ?? '') ?: null;

                    $errors = [];
                    if (empty($firstName)) $errors[] = "First name is required.";
                    if (empty($lastName)) $errors[] = "Last name is required.";
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
                    if (!in_array($classification, [null, 'TL', 'VSL'])) $errors[] = "Invalid classification selected.";
                    if ($academic_rank && !in_array($academic_rank, ['Instructor', 'Assistant Professor', 'Associate Professor', 'Professor', 'Chair Professor', 'Dean'])) {
                        $errors[] = "Invalid academic rank selected.";
                    }
                    if ($employment_type && !in_array($employment_type, ['Full-time', 'Part-time', 'Adjunct', 'Visiting', 'Emeritus', 'Contractual'])) {
                        $errors[] = "Invalid employment type selected.";
                    }

                    // Check for email uniqueness
                    $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
                    $stmt->execute([':email' => $email, ':user_id' => $userId]);
                    if ($stmt->fetch()) {
                        $errors[] = "Email is already in use by another user.";
                    }

                    $profilePicture = $this->handleProfilePictureUpload($userId);
                    if (is_string($profilePicture) && strpos($profilePicture, 'Error') === 0) {
                        $errors[] = $profilePicture;
                    }

                    if (empty($errors)) {
                        $this->db->beginTransaction();
                        try {
                            $stmt = $this->db->prepare("
                                UPDATE users 
                                SET first_name = :first_name, 
                                    last_name = :last_name, 
                                    email = :email, 
                                    phone = :phone,
                                    profile_picture = :profile_picture
                                WHERE user_id = :user_id
                            ");
                            $stmt->execute([
                                ':first_name' => $firstName,
                                ':last_name' => $lastName,
                                ':email' => $email,
                                ':phone' => $phone ?: null,
                                ':profile_picture' => $profilePicture ?: null,
                                ':user_id' => $userId
                            ]);

                            $stmt = $this->db->prepare("
                                UPDATE faculty 
                                SET classification = :classification,
                                    academic_rank = :academic_rank,
                                    employment_type = :employment_type
                                WHERE faculty_id = :faculty_id"
                            );
                            $stmt->execute([
                                ':classification' => $classification,
                                ':academic_rank' => $academic_rank,
                                ':employment_type' => $employment_type,
                                ':faculty_id' => $facultyId
                            ]);

                            $_SESSION['first_name'] = $firstName;
                            $_SESSION['last_name'] = $lastName;
                            $_SESSION['email'] = $email;
                            $_SESSION['phone'] = $phone;
                            $_SESSION['profile_picture'] = $profilePicture;

                            $this->db->commit();
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully'];
                        } catch (PDOException $e) {
                            $this->db->rollBack();
                            error_log("profile: Database error during update for user_id: $userId - " . $e->getMessage());
                            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to update profile: ' . $e->getMessage()];
                        }
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => implode("<br>", $errors)];
                    }
                } elseif (isset($_POST['remove_profile_picture'])) {
                    $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $userId]);
                    $currentPicture = $stmt->fetchColumn();

                    if ($currentPicture && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentPicture)) {
                        if (!unlink($_SERVER['DOCUMENT_ROOT'] . $currentPicture)) {
                            error_log("profile: Failed to delete profile picture: $currentPicture");
                            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to remove profile picture'];
                        } else {
                            $stmt = $this->db->prepare("UPDATE users SET profile_picture = NULL WHERE user_id = :user_id");
                            $stmt->execute([':user_id' => $userId]);
                            $_SESSION['profile_picture'] = null;
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile picture removed successfully'];
                        }
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'No profile picture to remove'];
                    }
                } elseif (isset($_POST['add_specialization'])) {
                    $courseId = (int)($_POST['course_id'] ?? 0);
                    $expertiseLevel = $_POST['expertise_level'] ?? 'Intermediate';

                    if ($courseId <= 0) {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please select a valid course'];
                    } elseif (!in_array($expertiseLevel, ['Beginner', 'Intermediate', 'Expert'])) {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid expertise level'];
                    } else {
                        $stmt = $this->db->prepare("SELECT course_id FROM courses WHERE course_id = :course_id");
                        $stmt->execute([':course_id' => $courseId]);
                        if (!$stmt->fetch()) {
                            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Selected course does not exist'];
                        } else {
                            $stmt = $this->db->prepare("
                                SELECT specialization_id FROM specializations 
                                WHERE faculty_id = :faculty_id AND course_id = :course_id
                            ");
                            $stmt->execute([':faculty_id' => $facultyId, ':course_id' => $courseId]);
                            if ($stmt->fetch()) {
                                $_SESSION['flash'] = ['type' => 'error', 'message' => 'This course is already a specialization'];
                            } else {
                                $stmt = $this->db->prepare("
                                    INSERT INTO specializations (faculty_id, course_id, expertise_level) 
                                    VALUES (:faculty_id, :course_id, :expertise_level)"
                                );
                                $stmt->execute([
                                    ':faculty_id' => $facultyId,
                                    ':course_id' => $courseId,
                                    ':expertise_level' => $expertiseLevel
                                ]);
                                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Specialization added successfully'];
                            }
                        }
                    }
                } elseif (isset($_POST['delete_specialization'])) {
                    $specializationId = (int)($_POST['specialization_id'] ?? 0);
                    if ($specializationId > 0) {
                        $stmt = $this->db->prepare("
                            DELETE FROM specializations 
                            WHERE specialization_id = :specialization_id AND faculty_id = :faculty_id"
                        );
                        $stmt->execute([
                            ':specialization_id' => $specializationId,
                            ':faculty_id' => $facultyId
                        ]);
                        $_SESSION['flash'] = ['type' => $specializationId > 0 ? 'success' : 'error', 'message' => $specializationId > 0 ? 'Specialization deleted successfully' : 'Invalid specialization'];
                    }
                }

                header('Location: /faculty/profile');
                exit;
            }

            $stmt = $this->db->prepare("
                SELECT u.*, f.*, d.department_name 
                FROM users u
                JOIN faculty f ON u.user_id = f.user_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE f.faculty_id = :faculty_id
            ");
            $stmt->execute([':faculty_id' => $facultyId]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                error_log("profile: No faculty data found for faculty_id: $facultyId");
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Faculty data not found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            $stmt = $this->db->prepare("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("
                SELECT s.*, c.course_code, c.course_name 
                FROM specializations s
                JOIN courses c ON s.course_id = c.course_id
                WHERE s.faculty_id = :faculty_id
                ORDER BY c.course_code
            ");
            $stmt->execute([':faculty_id' => $facultyId]);
            $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT s.course_id) as course_count
                FROM schedules s
                WHERE s.faculty_id = :faculty_id
            ");
            $stmt->execute([':faculty_id' => $facultyId]);
            $courseCount = $stmt->fetch(PDO::FETCH_ASSOC)['course_count'];

            $stmt = $this->db->prepare("
                SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) / 60 as teaching_hours
                FROM schedules
                WHERE faculty_id = :faculty_id
                AND day_of_week IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')
            ");
            $stmt->execute([':faculty_id' => $facultyId]);
            $teachingHours = $stmt->fetch(PDO::FETCH_ASSOC)['teaching_hours'] ?? 0;

            require_once __DIR__ . '/../views/faculty/profile.php';
        } catch (Exception $e) {
            error_log("profile: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'An error occurred'];
            header('Location: /faculty/dashboard');
            exit;
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
            return "/uploads/profile_pictures/{$filename}";
        } else {
            error_log("profile: Failed to move uploaded file for user_id: $userId to $uploadPath");
            return "Error: Failed to upload file.";
        }
    }

}