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
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
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
        try {
            if ($departmentId && $semester['department_id']) {
                $scheduleStmt = $this->db->prepare("
                    SELECT s.schedule_id, c.course_name, cr.room_name, f.first_name, f.last_name,
                           s.start_time, s.end_time, s.day_of_week
                    FROM schedules s
                    JOIN courses c ON s.course_id = c.course_id
                    JOIN classrooms cr ON s.classroom_id = cr.classroom_id
                    JOIN faculty f ON s.faculty_id = f.faculty_id
                    WHERE s.department_id = :department_id
                    AND s.semester_id = :semester_id
                    ORDER BY s.day_of_week, s.start_time
                ");
                $scheduleStmt->execute([':department_id' => $departmentId, ':semester_id' => $semester['deparment_id']]);
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
                    header('Location: /director/set-schedule-deadline');
                    exit;
                }

                $deadlineDate = DateTime::createFromFormat('Y-m-d H:i', $deadline);
                if (!$deadlineDate || $deadlineDate < new DateTime()) {
                    error_log("setScheduleDeadline: Deadline is invalid or in the past");
                    $_SESSION['error'] = 'Deadline must be a future date and time.';
                    header('Location: /director/set-schedule-deadline');
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

            require_once __DIR__ . '/../views/director/set_schedule_deadline.php';
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
                SELECT al.activity_id, al.activity_type, al.details, al.created_at, u.name as user_name
                FROM activity_log al
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

            require_once __DIR__ . '/../views/director/activity_monitor.php';
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

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

                $stmt = $this->db->prepare("UPDATE users SET name = :name, email = :email WHERE user_id = :user_id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':user_id' => $_SESSION['user_id']
                ]);

                error_log("profile: Updated profile for user_id: " . $_SESSION['user_id']);
                $_SESSION['success'] = 'Profile updated successfully';
                header('Location: /director/profile');
                exit;
            }

            $data = [
                'user' => $userData,
                'title' => 'Director Profile'
            ];

            require_once __DIR__ . '/../views/director/profile.php';
        } catch (PDOException $e) {
            error_log("profile: Database error - " . $e->getMessage());
            header('Location: /error?message=Database error');
            exit;
        }
    }

    
}
