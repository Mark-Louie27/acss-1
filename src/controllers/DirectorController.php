<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/ApiController.php';

class DirectorController
{
    public $db;
    private $userModel;
    public $api;

    public function __construct()
    {
        error_log("DirectorController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in DirectorController");
            die("Database connection failed. Please try again later.");
        }
        $this->userModel = new UserModel();
        $this->api = new ApiController();
        $this->restrictToDi();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function restrictToDi()
    {
        error_log("restrictToDi: Checking session - user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role_id: " . ($_SESSION['role_id'] ?? 'none'));
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
            error_log("restrictToDi: Redirecting to login due to unauthorized access");
            header('Location: /login?error=Unauthorized access');
            exit;
        }
    }

    private function getUserData()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, f.employment_type, f.academic_rank
                FROM users u
                LEFT JOIN faculty f ON u.user_id = f.user_id
                WHERE u.user_id = :user_id
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                // Fetch primary specialization for the director
                $specStmt = $this->db->prepare("
                    SELECT s.specialization_id, c.course_name
                    FROM specializations s
                    JOIN courses c ON s.course_id = c.course_id
                    WHERE s.faculty_id = :faculty_id AND s.is_primary_specialization = 1
                    LIMIT 1
                ");
                $specStmt->execute([':faculty_id' => $_SESSION['user_id']]);
                $specialization = $specStmt->fetch(PDO::FETCH_ASSOC);
                $user['course_specialization'] = $specialization ? $specialization['course_name'] : null;
                $user['specialization_id'] = $specialization ? $specialization['specialization_id'] : null;
                error_log("getUserData: Successfully fetched user data for user_id: " . $_SESSION['user_id']);
                return $user;
            } else {
                error_log("getUserData: No user found for user_id: " . $_SESSION['user_id']);
                return null;
            }
        } catch (PDOException $e) {
            error_log("getUserData: Database error - " . $e->getMessage());
            return null;
        }
    }

    public function dashboard()
    {
        $userData = $this->getUserData();
        if (!$userData) {
            error_log("dashboard: Failed to load user data for user_id: " . $_SESSION['user_id']);
            header('Location: /login?error=User data not found');
            exit;
        }

        // Fetch department_id from department_instructors
        $departmentId = null;
        $curriculumId = null;
        try {
            $stmt = $this->db->prepare("
                SELECT department_id FROM department_instructors 
                WHERE user_id = :user_id AND is_current = 1
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($department) {
                $departmentId = $department['department_id'];
            } else {
                error_log("dashboard: No department found for user_id: " . $_SESSION['user_id']);
                header('Location: /login?error=Department not assigned');
                exit;
            }
        } catch (PDOException $e) {
            error_log("dashboard: Failed to fetch department_id - " . $e->getMessage());
            // Allow partial load with error message
            $departmentId = null;
        }

        try {
            $semesterData = $this->api->getCurrentSemester();
            if (is_array($semesterData) && isset($semesterData['semester_id'], $semesterData['semester_name'])) {
                $semester = $semesterData;
            }
        } catch (Exception $e) {
            error_log("dashboard: Failed to fetch semester - " . $e->getMessage());
        }

        // Fetch pending approvals
        $pendingCount = 0;
        try {
            if ($curriculumId) {
                $pendingApprovalsStmt = $this->db->prepare("
                    SELECT COUNT(*) as pending_count
                    FROM curriculum_approvals
                    WHERE curriculum_id = :curriculum_id AND status = 'pending'
                ");
                $pendingApprovalsStmt->execute([':curriculum_id' => $curriculumId]);
                $pendingCount = $pendingApprovalsStmt->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log("dashboard: Failed to fetch pending approvals - " . $e->getMessage());
            $pendingCount = 0; // Fallback to 0
        }

        // Fetch current schedule deadline
        $deadline = null;
        try {
            if ($departmentId) {
                $deadlineStmt = $this->db->prepare("
                    SELECT deadline FROM schedule_deadlines 
                    WHERE department_id = :department_id 
                    ORDER BY deadline DESC LIMIT 1
                ");
                $deadlineStmt->execute([':department_id' => $departmentId]);
                $deadline = $deadlineStmt->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log("dashboard: Failed to fetch schedule deadline - " . $e->getMessage());
        }

        // Fetch class schedules
        $schedules = [];
        $query = "SELECT faculty_id FROM faculty WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userData['user_id']]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

        try {
        if ($faculty) {
            $scheduleQuery = "
                SELECT s.*, c.course_code, c.course_name, r.room_name, se.semester_name, se.academic_year
                FROM schedules s
                JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                JOIN semesters se ON s.semester_id = se.semester_id
                WHERE s.faculty_id = :faculty_id AND se.is_current = 1
                ORDER BY s.day_of_week, s.start_time";
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->execute([':faculty_id' => $faculty['faculty_id']]);
            $schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        } catch (PDOException $e) {
            error_log("dashboard: Failed to fetch schedules - " . $e->getMessage());
        }

        $data = [
            'user' => $userData,
            'pending_approvals' => $pendingCount,
            'deadline' => $deadline ? date('Y-m-d H:i:s', strtotime($deadline)) : null,
            'semester' => $semester,
            'schedules' => $schedules,
            'title' => 'Director Dashboard',
            'current_time' => date('h:i A T', time()), // e.g., 11:02 PM PST
            'has_db_error' => $departmentId === null || $pendingCount === null || $deadline === null || empty($schedules)
        ];

        require_once __DIR__ . '/../views/director/dashboard.php';
    }

    public function setScheduleDeadline()
    {
        try {
            $userData = $this->getUserData();
            if (!$userData) {
                error_log("setScheduleDeadline: Failed to load user data for user_id: " . $_SESSION['user_id']);
                header('Location: /login?error=User data not found');
                exit;
            }

            // Fetch department_id from department_instructors
            $stmt = $this->db->prepare("
                SELECT department_id FROM department_instructors 
                WHERE user_id = :user_id AND is_current = 1
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$department) {
                error_log("setScheduleDeadline: No department found for user_id: " . $_SESSION['user_id']);
                header('Location: /login?error=Department not assigned');
                exit;
            }
            $departmentId = $department['department_id'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $deadline = filter_input(INPUT_POST, 'deadline', FILTER_SANITIZE_STRING);
                if (!$deadline) {
                    error_log("setScheduleDeadline: Invalid deadline format");
                    $_SESSION['error'] = 'Please provide a valid deadline date and time.';
                    header('Location: /director/schedule-deadline');
                    exit;
                }

                $deadlineDate = DateTime::createFromFormat('Y-m-d H:i', $deadline);
                if (!$deadlineDate || $deadlineDate < new DateTime()) {
                    error_log("setScheduleDeadline: Deadline is invalid or in the past");
                    $_SESSION['error'] = 'Deadline must be a future date and time.';
                    header('Location: /director/schedule-deadline');
                    exit;
                }

                // Insert or update schedule deadline
                $stmt = $this->db->prepare("
                    INSERT INTO schedule_deadlines (department_id, deadline, created_at)
                    VALUES (:department_id, :deadline, NOW())
                    ON DUPLICATE KEY UPDATE deadline = :deadline, created_at = NOW()
                ");
                $stmt->execute([
                    ':department_id' => $departmentId,
                    ':deadline' => $deadlineDate->format('Y-m-d H:i:s')
                ]);

                error_log("setScheduleDeadline: Set deadline for department_id: $departmentId to " . $deadlineDate->format('Y-m-d H:i:s'));
                $_SESSION['success'] = 'Schedule deadline set successfully.';
                header('Location: /director/dashboard');
                exit;
            }

            // Fetch current deadline
            $deadlineStmt = $this->db->prepare("
                SELECT deadline FROM schedule_deadlines 
                WHERE department_id = :department_id 
                ORDER BY deadline DESC LIMIT 1
            ");
            $deadlineStmt->execute([':department_id' => $departmentId]);
            $currentDeadline = $deadlineStmt->fetchColumn();

            $data = [
                'user' => $userData,
                'title' => 'Set Schedule Deadline',
                'current_deadline' => $currentDeadline ? date('Y-m-d H:i:s', strtotime($currentDeadline)) : null
            ];

            require_once __DIR__ . '/../views/director/schedule_deadline.php';
        } catch (PDOException $e) {
            error_log("setScheduleDeadline: Database error - " . $e->getMessage());
            header('Location: /error?message=Database error');
            exit;
        }
    }

    public function monitor()
    {
        try {
            $userData = $this->getUserData();
            if (!$userData) {
                error_log("monitor: Failed to load user data for user_id: " . $_SESSION['user_id']);
                header('Location: /login?error=User data not found');
                exit;
            }

            $stmt = $this->db->prepare("
                SELECT department_id FROM department_instructors 
                WHERE user_id = :user_id AND is_current = 1
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$department) {
                error_log("monitor: No department found for user_id: " . $_SESSION['user_id']);
                header('Location: /login?error=Department not assigned');
                exit;
            }
            $departmentId = $department['department_id'];

            // Fetch activity log for the department
            $activityStmt = $this->db->prepare("
                SELECT al.log_id, al.action_type, al.action_description, al.created_at, u.first_name, u.last_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.user_id
                WHERE al.department_id = :department_id
                ORDER BY al.created_at DESC
            ");
            $activityStmt->execute([':department_id' => $departmentId]);
            $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [
                'user' => $userData,
                'activities' => $activities,
                'title' => 'Activity Monitor'
            ];

            require_once __DIR__ . '/../views/director/monitor.php';
        } catch (PDOException $e) {
            error_log("monitor: Database error - " . $e->getMessage());
            header('Location: /error?message=Database error');
            exit;
        }
    }

    public function profile()
    {
        try {
            $userData = $this->getUserData();
            if (!$userData) {
                error_log("profile: Failed to load user data for user_id: " . $_SESSION['user_id']);
                header('Location: /login?error=User data not found');
                exit;
            }

            // Fetch all specializations for the director
            $specStmt = $this->db->prepare("
                SELECT s.specialization_id, c.course_name, s.expertise_level
                FROM specializations s
                JOIN courses c ON s.course_id = c.course_id
                WHERE s.faculty_id = :faculty_id 
                ORDER BY c.course_name
            ");
            $specStmt->execute([':faculty_id' => $_SESSION['user_id']]);
            $specializations = $specStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $middleName = trim($_POST['middle_name'] ?? '');
                $suffix = trim($_POST['suffix'] ?? '');
                $title = trim($_POST['title'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? null);
                $academic_rank = trim($_POST['academic_rank'] ?? '') ?: null;
                $employment_type = trim($_POST['employment_type'] ?? '') ?: null;
                $course_specialization = trim($_POST['course_specialization'] ?? '') ?: null;
                $expertise_level = trim($_POST['expertise_level'] ?? 'Intermediate'); // Default to Intermediate

                // Handle profile picture upload
                $profilePicture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/public/uploads/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                        $profilePicture = '/uploads/' . $fileName;
                    } else {
                        error_log("profile: Failed to upload profile picture for user_id: " . $_SESSION['user_id']);
                    }
                }

                $this->db->beginTransaction();

                // Update users table
                $fullName = trim("$firstName $middleName $lastName $suffix");
                $updateData = [
                    ':name' => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':user_id' => $_SESSION['user_id']
                ];
                $updateQuery = "UPDATE users SET name = :name, email = :email";
                if ($title) $updateQuery .= ", title = :title";
                if ($phone) $updateQuery .= ", phone = :phone";
                if ($profilePicture) $updateQuery .= ", profile_picture = :profile_picture";
                $stmt = $this->db->prepare($updateQuery);
                $stmt->execute(array_merge($updateData, [':title' => $title, ':profile_picture' => $profilePicture]));

                // Update faculty table
                if ($employment_type || $academic_rank) {
                    $facultyStmt = $this->db->prepare("
                        INSERT INTO faculty (user_id, employment_type, academic_rank)
                        VALUES (:user_id, :employment_type, :academic_rank)
                        ON DUPLICATE KEY UPDATE employment_type = :employment_type, academic_rank = :academic_rank
                    ");
                    $facultyStmt->execute([':user_id' => $_SESSION['user_id'], ':employment_type' => $employment_type, ':academic_rank' => $academic_rank]);
                }

                // Update specialization
                if ($course_specialization) {
                    $courseStmt = $this->db->prepare("SELECT course_id FROM courses WHERE course_name = :course_name");
                    $courseStmt->execute([':course_name' => $course_specialization]);
                    $courseId = $courseStmt->fetchColumn();
                    if ($courseId) {
                        // Check if specialization exists for this faculty
                        $specCheckStmt = $this->db->prepare("
                            SELECT specialization_id FROM specializations 
                            WHERE faculty_id = :faculty_id AND course_id = :course_id
                        ");
                        $specCheckStmt->execute([':faculty_id' => $_SESSION['user_id'], ':course_id' => $courseId]);
                        $specializationId = $specCheckStmt->fetchColumn();

                        if ($specializationId) {
                            $updateSpecStmt = $this->db->prepare("
                                UPDATE specializations 
                                SET expertise_level = :expertise_level, is_primary_specialization = 1
                                WHERE specialization_id = :specialization_id
                            ");
                            $updateSpecStmt->execute([':expertise_level' => $expertise_level, ':specialization_id' => $specializationId]);
                        } else {
                            $insertSpecStmt = $this->db->prepare("
                                INSERT INTO specializations (faculty_id, course_id, expertise_level, is_primary_specialization)
                                VALUES (:faculty_id, :course_id, :expertise_level, 1)
                            ");
                            $insertSpecStmt->execute([':faculty_id' => $_SESSION['user_id'], ':course_id' => $courseId, ':expertise_level' => $expertise_level]);
                        }
                        // Reset other specializations to non-primary
                        $resetStmt = $this->db->prepare("
                            UPDATE specializations 
                            SET is_primary_specialization = 0 
                            WHERE faculty_id = :faculty_id AND specialization_id != LAST_INSERT_ID()
                        ");
                        $resetStmt->execute([':faculty_id' => $_SESSION['user_id']]);
                    }
                }

                $this->db->commit();
                error_log("profile: Updated profile for user_id: " . $_SESSION['user_id']);
                $_SESSION['success'] = 'Profile updated successfully';
                header('Location: /director/profile');
                exit;
            }

            $data = [
                'user' => $userData,
                'specializations' => $specializations,
                'title' => 'Director Profile',
                'current_time' => date('h:i A T', time()) // e.g., 04:41 PM PST
            ];

            require_once __DIR__ . '/../views/director/profile.php';
        } catch (PDOException $e) {
            error_log("profile: Database error - " . $e->getMessage());
            header('Location: /error?message=Database error');
            exit;
        }
    }
}
