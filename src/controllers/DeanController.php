<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/EmailService.php';

class DeanController
{
    private $db;
    private $userModel;
    private $emailService;
    private $authService;

    public function __construct()
    {
        error_log("DeanController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in DeanController");
            die("Database connection failed. Please try again later.");
        }
        $this->userModel = new UserModel();
        $this->authService = new AuthService($this->db);
        $this->restrictToDean();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->emailService = new EmailService();
    }

    private function restrictToDean()
    {
        error_log("restrictToDean: Checking session - user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role_id: " . ($_SESSION['role_id'] ?? 'none'));
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
            error_log("restrictToDean: Redirecting to login due to unauthorized access");
            header('Location: /login?error=Unauthorized access');
            exit;
        }
    }

    private function logAuthActivity($userId, $action, $ipAddress, $userAgent, $identifier = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO auth_logs 
                (user_id, action, ip_address, user_agent, identifier, created_at) 
                VALUES (:user_id, :action, :ip_address, :user_agent, :identifier, NOW())
            ");
            $params = [
                ':user_id' => $userId,
                ':action' => $action,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':identifier' => $identifier ?: session_id()
            ];
            error_log("logAuthActivity: Logging - Action: $action, User: $userId, IP: $ipAddress");
            $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("logAuthActivity: Failed to log auth activity - " . $e->getMessage());
        }
    }

    private function getDepartmentLogins($collegeId)
    {
        try {
            $query = "
            SELECT 
                u.user_id,
                u.first_name,
                u.last_name,
                COALESCE(d.department_name, 'No Department') AS department_name,
                la.action,
                la.ip_address,
                la.user_agent,
                la.created_at
            FROM auth_logs la
            JOIN users u ON la.user_id = u.user_id
            JOIN faculty f ON u.user_id = f.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            JOIN departments d ON fd.department_id = d.department_id
            WHERE d.college_id = :college_id AND la.action = 'Login'
            ORDER BY la.created_at DESC
            LIMIT 10";
            $stmt = $this->db->prepare($query);
            error_log("getDepartmentLogins: Executing query with college_id=$collegeId");
            $stmt->execute([':college_id' => $collegeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getDepartmentLogins: Fetched " . count($results) . " login records. First result: " . json_encode($results[0] ?? 'None'));
            return $results;
        } catch (PDOException $e) {
            error_log("getDepartmentLogins: Failed to fetch logins - " . $e->getMessage());
            return [];
        }
    }

    public function dashboard()
    {
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($userId);

        // Log dashboard access
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $this->logAuthActivity($userId, 'Access Dashboard', $ipAddress, $userAgent);

        // Get college details for the dean
        $query = "
            SELECT d.college_id, c.college_name 
            FROM deans d
            JOIN colleges c ON d.college_id = c.college_id
            WHERE d.user_id = :user_id AND d.is_current = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $college = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$college) {
            error_log("No college found for dean user_id: $userId");
            return ['error' => 'No college assigned to this dean'];
        }

        // Fetch current semester
        $semesterQuery = "SELECT semester_name, academic_year FROM semesters WHERE is_current = 1 LIMIT 1";
        $semesterStmt = $this->db->prepare($semesterQuery);
        $semesterStmt->execute();
        $currentSemester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
        $currentSemesterDisplay = $currentSemester ?
            "{$currentSemester['semester_name']} {$currentSemester['academic_year']}" : 'Not Set';

        // Fetch dean's schedule
        $schedules = [];
        $query = "SELECT faculty_id FROM faculty WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

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

        // Fetch dashboard statistics
        $stats = [
            'total_faculty' => $this->getCollegeStats($college['college_id'], 'faculty'),
            'total_classrooms' => $this->getCollegeStats($college['college_id'], 'classrooms'),
            'total_departments' => $this->getCollegeStats($college['college_id'], 'departments'),
            'pending_approvals' => $this->getPendingApprovals($college['college_id'])
        ];

        $activities = $this->getDepartmentActivities($college['college_id']);
        $departmentLogins = $this->getDepartmentLogins($college['college_id']);

        // Pass semester, schedules, and logins to the view
        $currentSemester = $currentSemesterDisplay;
        require_once __DIR__ . '/../views/dean/dashboard.php';
    }

    public function activities()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                error_log("activities: No user_id in session");
                throw new Exception('User session not found');
            }

            $collegeId = $this->getDeanCollegeId($userId);
            if (!$collegeId) {
                error_log("activities: No college found for dean user_id: $userId");
                throw new Exception('No college assigned to this dean');
            }

            // Get filter parameters
            $departmentId = $_GET['department_id'] ?? null;
            $date = $_GET['date'] ?? null;
            $activities = $this->getDepartmentActivities($collegeId, $departmentId, $date);
            if ($activities === false) {
                throw new Exception('Failed to fetch activities');
            }

            // Load activities view
            require_once __DIR__ . '/../views/dean/activities.php';
        } catch (PDOException $e) {
            error_log("activities: Database error - " . $e->getMessage());
            $activities = [];
            $error = "Database error occurred. Please try again.";
            require_once __DIR__ . '/../views/dean/activities.php';
        } catch (Exception $e) {
            error_log("activities: Error - " . $e->getMessage());
            $activities = [];
            $error = $e->getMessage();
            require_once __DIR__ . '/../views/dean/activities.php';
        }
    }

    private function getDepartmentActivities($collegeId, $departmentId = null, $date = null)
    {
        try {
            $query = "
            SELECT al.*, d.department_name
            FROM activity_logs al
            JOIN departments d ON al.department_id = d.department_id
            WHERE d.college_id = :college_id";

            $params = [':college_id' => $collegeId];

            if ($departmentId) {
                $query .= " AND al.department_id = :department_id";
                $params[':department_id'] = $departmentId;
            }
            if ($date) {
                $query .= " AND DATE(al.created_at) = :date";
                $params[':date'] = $date;
            }

            $query .= " ORDER BY al.created_at DESC
                   LIMIT 10"; // Limit to 10 recent activities
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getDepartmentActivities: Error - " . $e->getMessage());
            return [];
        }
    }

    // Add this for "View All" if not already present
    public function getAllDepartmentActivities($collegeId, $departmentId = null, $date = null)
    {
        try {
            $query = "
            SELECT al.*, d.department_name
            FROM activity_logs al
            JOIN departments d ON al.department_id = d.department_id
            WHERE d.college_id = :college_id";

            $params = [':college_id' => $collegeId];

            if ($departmentId) {
                $query .= " AND al.department_id = :department_id";
                $params[':department_id'] = $departmentId;
            }
            if ($date) {
                $query .= " AND DATE(al.created_at) = :date";
                $params[':date'] = $date;
            }

            $query .= " ORDER BY al.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getAllDepartmentActivities: Error - " . $e->getMessage());
            return [];
        }
    }

    private function getCollegeStats($collegeId, $type)
    {
        try {
            $query = "";
            switch ($type) {
                case 'faculty':
                    $query = "
                        SELECT COUNT(*) 
                        FROM faculty_departments fd 
                        JOIN departments d ON fd.department_id = d.department_id 
                        WHERE d.college_id = :college_id";
                    break;
                case 'classrooms':
                    $query = "
                        SELECT COUNT(*) 
                        FROM classrooms c 
                        JOIN departments d ON c.department_id = d.department_id 
                        WHERE d.college_id = :college_id";
                    break;
                case 'departments':
                    $query = "
                        SELECT COUNT(*) 
                        FROM departments d 
                        WHERE d.college_id = :college_id";
                    break;
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching $type stats: " . $e->getMessage());
            return 0;
        }
    }

    private function getPendingApprovals($collegeId)
    {
        try {
            $query = "
                SELECT COUNT(*) 
                FROM curriculum_approvals ca 
                JOIN curricula c ON ca.curriculum_id = c.curriculum_id 
                JOIN departments d ON c.department_id = d.department_id 
                WHERE d.college_id = :college_id AND ca.status = 'Pending' AND ca.approval_level = 2";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching pending approvals: " . $e->getMessage());
            return 0;
        }
    }

    public function mySchedule()
    {
        $userId = $_SESSION['user_id'];

        // Get dean's faculty ID
        $query = "SELECT faculty_id FROM faculty WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

        $schedules = [];
        if ($faculty) {
            $query = "
                SELECT s.*, c.course_code, c.course_name, r.room_name, se.semester_name, se.academic_year
                FROM schedules s
                JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                JOIN semesters se ON s.semester_id = se.semester_id
                WHERE s.faculty_id = :faculty_id AND se.is_current = 1
                ORDER BY s.day_of_week, s.start_time";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':faculty_id' => $faculty['faculty_id']]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Load schedule view
        require_once __DIR__ . '/../views/dean/schedule.php';
    }

    public function classroom()
    {
        $userId = $_SESSION['user_id'];
        $collegeId = $this->getDeanCollegeId($userId);

        // Add this line before requiring the view
        $controller = $this;

        if (isset($_POST['toggle_availability'])) {
            $roomId = $_POST['room_id'];
            $currentAvailability = $_POST['current_availability'];
            $nextAvailability = [
                'available' => 'unavailable',
                'unavailable' => 'under_maintenance',
                'under_maintenance' => 'available'
            ][$currentAvailability];
            $query = "UPDATE classrooms SET availability = :availability, updated_at = NOW() WHERE room_id = :room_id";
            $stmt = $this->db->prepare($query);
            try {
                $stmt->execute([':availability' => $nextAvailability, ':room_id' => $roomId]);
                header("Location: /dean/classroom?success=Availability updated successfully");
            } catch (PDOException $e) {
                error_log("Error updating availability: " . $e->getMessage());
                header("Location: /dean/classroom?error=Failed to update availability");
            }
            exit;
        }

        if (isset($_POST['update_classroom'])) {
            $roomId = $_POST['room_id'];
            $roomName = $_POST['room_name'];
            $building = $_POST['building'];
            $departmentId = $_POST['department_id'];
            $capacity = $_POST['capacity'];
            $roomType = $_POST['room_type'];
            $shared = isset($_POST['shared']) ? 1 : 0;
            $availability = $_POST['availability'];
            $query = "UPDATE classrooms SET room_name = :room_name, building = :building, department_id = :department_id, capacity = :capacity, room_type = :room_type, shared = :shared, availability = :availability, updated_at = NOW() WHERE room_id = :room_id";
            $stmt = $this->db->prepare($query);
            try {
                $stmt->execute([
                    ':room_name' => $roomName,
                    ':building' => $building,
                    ':department_id' => $departmentId,
                    ':capacity' => $capacity,
                    ':room_type' => $roomType,
                    ':shared' => $shared,
                    ':availability' => $availability,
                    ':room_id' => $roomId
                ]);
                header("Location: /dean/classroom?success=Classroom updated successfully");
            } catch (PDOException $e) {
                error_log("Error updating classroom: " . $e->getMessage());
                header("Location: /dean/classroom?error=Failed to update classroom");
            }
            exit;
        }

        if (!$collegeId) {
            error_log("No college found for dean user_id: $userId");
            return ['error' => 'No college assigned to this dean'];
        }

        // Handle add classroom
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_classroom'])) {
            $this->addClassroom($_POST, $collegeId);
        }

        // Handle room reservation approval
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
            $this->handleRoomReservation($_POST);
        }

        // Fetch classrooms
        $query = "
        SELECT c.*, d.department_name
        FROM classrooms c
        JOIN departments d ON c.department_id = d.department_id
        WHERE d.college_id = :college_id
        ORDER BY c.building, c.room_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':college_id' => $collegeId]);
        $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load classroom management view
        require_once __DIR__ . '/../views/dean/classroom.php';
    }

    private function addClassroom($data, $collegeId)
    {
        try {
            // Verify department belongs to Dean's college
            $query = "SELECT department_id FROM departments WHERE department_id = :department_id AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $data['department_id'],
                ':college_id' => $collegeId
            ]);
            if (!$stmt->fetch()) {
                error_log("Invalid department_id for college_id: $collegeId");
                header('Location: /dean/classroom?error=Invalid department selected');
                exit;
            }

            if (isset($_POST['add_classroom'])) {
                $roomName = $_POST['room_name'];
                $building = $_POST['building'];
                $departmentId = $_POST['department_id'];
                $capacity = $_POST['capacity'];
                $roomType = $_POST['room_type'];
                $shared = isset($_POST['shared']) ? 1 : 0;
                $availability = $_POST['availability'];
                $query = "INSERT INTO classrooms (room_name, building, department_id, capacity, room_type, shared, availability, created_at, updated_at) VALUES (:room_name, :building, :department_id, :capacity, :room_type, :shared, :availability, NOW(), NOW())";
                $stmt = $this->db->prepare($query);
                try {
                    $stmt->execute([
                        ':room_name' => $roomName,
                        ':building' => $building,
                        ':department_id' => $departmentId,
                        ':capacity' => $capacity,
                        ':room_type' => $roomType,
                        ':shared' => $shared,
                        ':availability' => $availability
                    ]);
                    header("Location: /dean/classroom?success=Classroom added successfully");
                } catch (PDOException $e) {
                    error_log("Error adding classroom: " . $e->getMessage());
                    header("Location: /dean/classroom?error=Failed to add classroom");
                }
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error adding classroom: " . $e->getMessage());
            header('Location: /dean/classroom?error=Failed to add classroom');
        }
    }

    private function handleRoomReservation($data)
    {
        try {
            $query = "
                UPDATE room_reservations 
                SET approval_status = :status, approved_by = :approved_by
                WHERE reservation_id = :reservation_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':status' => $data['status'],
                ':approved_by' => $_SESSION['user_id'],
                ':reservation_id' => $data['reservation_id']
            ]);
            header('Location: /dean/classroom?success=Reservation updated');
        } catch (PDOException $e) {
            error_log("Error updating room reservation: " . $e->getMessage());
            header('Location: /dean/classroom?error=Failed to update reservation');
        }
    }

    public function faculty()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("faculty: No user_id in session");
            http_response_code(401);
            return ['error' => 'No user session'];
        }

        $collegeId = $this->getDeanCollegeId($userId);
        if (!$collegeId) {
            error_log("faculty: No college found for dean user_id: $userId");
            http_response_code(403);
            return ['error' => 'No college assigned to this dean'];
        }

        try {
            // Initialize variables for the view
            $programdeans = [];
            $faculty = [];
            $pendingUsers = [];
            $departments = [];
            $currentSemester = ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            $error = null;

            // Fetch Program deans
            $querydeans = "
            SELECT u.user_id, u.employee_id, u.email, u.title, u.first_name, u.middle_name, u.last_name, u.suffix, u.profile_picture, u.is_active, 
                   pc.program_id, p.program_name, d.department_name, d.department_id, c.college_name
            FROM users u
            JOIN program_deans pc ON u.user_id = pc.user_id
            JOIN programs p ON pc.program_id = p.program_id
            JOIN departments d ON p.department_id = d.department_id
            JOIN colleges c ON d.college_id = c.college_id
            WHERE d.college_id = :college_id AND pc.is_current = 1 AND u.role_id = 5
            ORDER BY u.last_name, u.first_name";
            $stmtdeans = $this->db->prepare($querydeans);
            if (!$stmtdeans) {
                throw new PDOException("Failed to prepare querydeans: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtdeans->execute([':college_id' => $collegeId]);
            $programdeans = $stmtdeans->fetchAll(PDO::FETCH_ASSOC);
            error_log("faculty: Fetched " . count($programdeans) . " program deans");

            // Fetch Faculty
            $queryFaculty = "
            SELECT u.user_id, u.employee_id, u.email, u.title, u.first_name, u.middle_name, u.last_name, u.suffix, u.profile_picture, u.is_active, 
                   f.academic_rank, f.employment_type, d.department_name, d.department_id, c.college_name,
                   co.course_name AS specialization, s.expertise_level
            FROM users u
            JOIN faculty f ON u.user_id = f.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            JOIN departments d ON fd.department_id = d.department_id
            JOIN colleges c ON d.college_id = c.college_id
            LEFT JOIN specializations s ON f.faculty_id = s.faculty_id AND s.is_primary_specialization = 1
            LEFT JOIN courses co ON s.course_id = co.course_id
            WHERE d.college_id = :college_id AND u.role_id = 6 AND fd.is_primary = 1
            ORDER BY u.last_name, u.first_name";
            $stmtFaculty = $this->db->prepare($queryFaculty);
            if (!$stmtFaculty) {
                throw new PDOException("Failed to prepare queryFaculty: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtFaculty->execute([':college_id' => $collegeId]);
            $faculty = $stmtFaculty->fetchAll(PDO::FETCH_ASSOC);
            error_log("faculty: Fetched " . count($faculty) . " faculty members");

            // Fetch pending users
            $queryPending = "
            SELECT u.user_id, u.employee_id, u.email, u.title, u.first_name, u.middle_name, u.last_name, u.suffix, u.profile_picture, u.is_active, 
                   u.role_id, r.role_name, f.academic_rank, f.employment_type, d.department_name, d.department_id, c.college_name
            FROM users u
            JOIN faculty f ON u.user_id = f.user_id
            JOIN roles r ON u.role_id = r.role_id
            JOIN departments d ON u.department_id = d.department_id
            JOIN colleges c ON d.college_id = c.college_id
            WHERE u.college_id = :college_id AND u.is_active = 0 AND u.role_id IN (5, 6)
            ORDER BY u.created_at";
            $stmtPending = $this->db->prepare($queryPending);
            if (!$stmtPending) {
                throw new PDOException("Failed to prepare queryPending: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtPending->execute([':college_id' => $collegeId]);
            $pendingUsers = $stmtPending->fetchAll(PDO::FETCH_ASSOC);
            error_log("faculty: Fetched " . count($pendingUsers) . " pending users");

            // Fetch departments for filter
            $queryDepartments = "
            SELECT department_id, department_name
            FROM departments
            WHERE college_id = :college_id
            ORDER BY department_name";
            $stmtDepartments = $this->db->prepare($queryDepartments);
            if (!$stmtDepartments) {
                throw new PDOException("Failed to prepare queryDepartments: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtDepartments->execute([':college_id' => $collegeId]);
            $departments = $stmtDepartments->fetchAll(PDO::FETCH_ASSOC);
            error_log("faculty: Fetched " . count($departments) . " departments");

            // Fetch current semester
            $querySemester = "
            SELECT semester_name, academic_year
            FROM semesters
            WHERE is_current = 1
            LIMIT 1";
            $stmtSemester = $this->db->prepare($querySemester);
            if (!$stmtSemester) {
                throw new PDOException("Failed to prepare querySemester: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtSemester->execute();
            $currentSemester = $stmtSemester->fetch(PDO::FETCH_ASSOC) ?: ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            error_log("faculty: Current semester - " . json_encode($currentSemester));

            // Handle POST actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                if (isset($_POST['action'], $_POST['user_id']) && in_array($_POST['action'], ['activate', 'deactivate'])) {
                    $result = $this->handleUserAction($_POST);
                    echo json_encode($result);
                    exit;
                } else {
                    error_log("faculty: Invalid POST data");
                    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
                    exit;
                }
            }

            // Load faculty management view
            require_once __DIR__ . '/../views/dean/faculty.php';
        } catch (PDOException $e) {
            error_log("faculty: PDO Error - " . $e->getMessage());
            http_response_code(500);
            $error = "Database error: " . $e->getMessage();
            $programdeans = $faculty = $pendingUsers = $departments = [];
            $currentSemester = ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            require_once __DIR__ . '/../views/dean/faculty.php';
        } catch (Exception $e) {
            error_log("faculty: Error - " . $e->getMessage());
            http_response_code(500);
            $error = $e->getMessage();
            $programdeans = $faculty = $pendingUsers = $departments = [];
            $currentSemester = ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            require_once __DIR__ . '/../views/dean/faculty.php';
        }
    }

    private function handleUserAction($data)
    {
        $userId = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        $action = $data['action'];
        $collegeId = $this->getDeanCollegeId($_SESSION['user_id']);
        $departmentId = $this->getUserDepartmentId($userId); // Assuming this method exists to get department_id

        if (!$userId || !$collegeId) {
            error_log("handleUserAction: Invalid user_id=$userId or college_id=$collegeId");
            return ['success' => false, 'error' => 'Invalid user or college'];
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'deactivate') {
                $query = "UPDATE users SET is_active = 0 WHERE user_id = :user_id AND college_id = :college_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':user_id' => $userId, ':college_id' => $collegeId]);
                error_log("handleUserAction: Deactivated user_id=$userId");
                $message = 'User account deactivated successfully';
                $this->logActivity($_SESSION['user_id'], $departmentId, 'Deactivate User', "Deactivated user ID $userId", 'users', $userId);
            } elseif ($action === 'activate') {
                $query = "UPDATE users SET is_active = 1 WHERE user_id = :user_id AND college_id = :college_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':user_id' => $userId, ':college_id' => $collegeId]);
                error_log("handleUserAction: Activated user_id=$userId");

                // Fetch user details for email
                $query = "SELECT u.email, u.first_name, u.last_name, u.role_id AS user_role_id, r.role_name 
                      FROM users u 
                      JOIN roles r ON u.role_id = r.role_id 
                      WHERE u.user_id = :user_id";
                $stmt = $this->db->prepare($query);
                if (!$stmt) {
                    throw new PDOException("Failed to prepare user details query: " . implode(', ', $this->db->errorInfo()));
                }
                $stmt->execute([':user_id' => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    error_log("handleUserAction: Fetched user details for user_id=$userId, role_id={$user['user_role_id']}, role_name={$user['role_name']}");
                    $this->emailService->sendApprovalEmail(
                        $user['email'],
                        $user['first_name'] . ' ' . $user['last_name'],
                        $user['role_name']
                    );
                } else {
                    error_log("handleUserAction: No user found for user_id=$userId");
                }
                $message = 'User account activated successfully';
                $this->logActivity($_SESSION['user_id'], $departmentId, 'Activate User', "Activated user ID $userId", 'users', $userId);
            } else {
                throw new Exception("Invalid action: $action");
            }

            $this->db->commit();
            return ['success' => true, 'message' => $message];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("handleUserAction: Error - " . $e->getMessage());
            return ['success' => false, 'error' => 'An error occurred while processing the action: ' . $e->getMessage()];
        }
    }

    // Assumed logActivity method (add to class if not present)
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

    // Assumed helper method to get department_id for a user
    private function getUserDepartmentId($userId)
    {
        $query = "SELECT department_id FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['department_id'] : null;
    }

    public function search()
    {
        $userId = $_SESSION['user_id'];
        $collegeId = $this->getDeanCollegeId($userId);

        // Add this line before requiring the view
        $controller = $this;

        $results = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term'])) {
            $searchTerm = '%' . $_POST['search_term'] . '%';
            $query = "
                SELECT u.*, f.academic_rank, d.department_name
                FROM users u
                JOIN faculty f ON u.user_id = f.user_id
                JOIN departments d ON f.department_id = d.department_id
                WHERE d.college_id = :college_id 
                AND (u.first_name LIKE :search_term OR u.last_name LIKE :search_term OR u.email LIKE :search_term)
                AND u.is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':college_id' => $collegeId,
                ':search_term' => $searchTerm
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Load search view
        require_once __DIR__ . '/../views/dean/search.php';
    }

    public function courses()
    {
        $userId = $_SESSION['user_id'];
        $collegeId = $this->getDeanCollegeId($userId);

        // Initialize variables
        $courses = [];
        $departments = [];
        $programs = [];
        $totalCourses = 0;

        // Pagination parameters
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;

        if ($collegeId) {
            // Get filter parameters
            $departmentFilter = isset($_GET['department']) ? (int)$_GET['department'] : null;
            $programFilter = isset($_GET['program']) ? (int)$_GET['program'] : null;
            $yearLevelFilter = isset($_GET['year_level']) ? $_GET['year_level'] : null;
            $statusFilter = isset($_GET['status']) ? $_GET['status'] : '1';

            // Base query for counting
            $countQuery = "SELECT COUNT(*) as total 
                  FROM courses c
                  JOIN departments d ON c.department_id = d.department_id
                  WHERE d.college_id = :college_id";

            // Base query for data
            $query = "SELECT 
                c.course_id,
                c.course_code,
                c.course_name,
                c.units,
                c.lecture_hours,
                c.lab_hours,
                c.semester,
                c.is_active,
                d.department_name,
                p.program_name,
                p.program_code,
                cl.college_name
            FROM courses c
            JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN programs p ON c.program_id = p.program_id
            JOIN colleges cl ON d.college_id = cl.college_id
            WHERE d.college_id = :college_id";

            // Add filters to both queries
            $params = [':college_id' => $collegeId];
            $countParams = [':college_id' => $collegeId];

            if ($departmentFilter) {
                $query .= " AND c.department_id = :department_id";
                $countQuery .= " AND c.department_id = :department_id";
                $params[':department_id'] = $departmentFilter;
                $countParams[':department_id'] = $departmentFilter;
            }

            if ($programFilter) {
                $query .= " AND c.program_id = :program_id";
                $countQuery .= " AND c.program_id = :program_id";
                $params[':program_id'] = $programFilter;
                $countParams[':program_id'] = $programFilter;
            }

            if ($statusFilter !== '') {
                $query .= " AND c.is_active = :is_active";
                $countQuery .= " AND c.is_active = :is_active";
                $params[':is_active'] = (int)$statusFilter;
                $countParams[':is_active'] = (int)$statusFilter;
            }

            // Get total count
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($countParams);
            $totalCourses = $countStmt->fetchColumn();

            // Add pagination to main query
            $query .= " ORDER BY d.department_name, c.course_code 
               LIMIT :offset, :per_page";

            // Calculate offset
            $offset = ($currentPage - 1) * $perPage;
            $params[':offset'] = $offset;
            $params[':per_page'] = $perPage;

            // Get paginated courses
            $stmt = $this->db->prepare($query);

            // Bind parameters with proper types
            foreach ($params as $key => $value) {
                $paramType = PDO::PARAM_STR;
                if (in_array($key, [':college_id', ':department_id', ':program_id', ':is_active', ':offset', ':per_page'])) {
                    $paramType = PDO::PARAM_INT;
                }
                $stmt->bindValue($key, $value, $paramType);
            }

            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all departments in the college
            $deptQuery = "SELECT department_id, department_name 
                 FROM departments 
                 WHERE college_id = :college_id 
                 ORDER BY department_name";
            $deptStmt = $this->db->prepare($deptQuery);
            $deptStmt->execute([':college_id' => $collegeId]);
            $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all programs in the college
            $programQuery = "SELECT p.program_id, p.program_name, p.program_code, d.department_name
                    FROM programs p
                    JOIN departments d ON p.department_id = d.department_id
                    WHERE d.college_id = :college_id
                    ORDER BY p.program_name";
            $programStmt = $this->db->prepare($programQuery);
            $programStmt->execute([':college_id' => $collegeId]);
            $programs = $programStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Calculate total pages
        $totalPages = ceil($totalCourses / $perPage);

        // Load courses view with all data
        require_once __DIR__ . '/../views/dean/courses.php';
    }

    public function curriculum()
    {
        $userId = $_SESSION['user_id'];
        $collegeId = $this->getDeanCollegeId($userId);

        // Add this line before requiring the view
        $controller = $this;

        // Handle add curriculum
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_curriculum'])) {
            $this->addCurriculum($_POST, $collegeId);
        }

        // Handle curriculum approval
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approval_id'])) {
            $this->handleCurriculumApproval($_POST);
        }

        $curricula = [];
        if ($collegeId) {
            $query = "
                SELECT c.*, d.department_name
                FROM curricula c
                JOIN departments d ON c.department_id = d.department_id
                WHERE d.college_id = :college_id
                ORDER BY c.effective_year DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            $curricula = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Load curriculum view
        require_once __DIR__ . '/../views/dean/curriculum.php';
    }

    private function addCurriculum($data, $collegeId)
    {
        try {
            // Verify department belongs to Dean's college
            $query = "SELECT department_id FROM departments WHERE department_id = :department_id AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $data['department_id'],
                ':college_id' => $collegeId
            ]);
            if (!$stmt->fetch()) {
                error_log("Invalid department_id for college_id: $collegeId");
                header('Location: /dean/curriculum?error=Invalid department selected');
                exit;
            }

            $query = "
                INSERT INTO curricula (curriculum_name, department_id, effective_year, status, created_at, updated_at)
                VALUES (:curriculum_name, :department_id, :effective_year, 'Pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':curriculum_name' => $data['curriculum_name'],
                ':department_id' => $data['department_id'],
                ':effective_year' => $data['effective_year']
            ]);

            // Add to curriculum_approvals
            $curriculumId = $this->db->lastInsertId();
            $query = "
                INSERT INTO curriculum_approvals (curriculum_id, approval_level, status, created_at, updated_at)
                VALUES (:curriculum_id, 2, 'Pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':curriculum_id' => $curriculumId]);

            header('Location: /dean/curriculum?success=Curriculum added successfully');
        } catch (PDOException $e) {
            error_log("Error adding curriculum: " . $e->getMessage());
            header('Location: /dean/curriculum?error=Failed to add curriculum');
        }
    }

    private function handleCurriculumApproval($data)
    {
        try {
            $query = "
                UPDATE curriculum_approvals 
                SET status = :status, comments = :comments, updated_at = CURRENT_TIMESTAMP
                WHERE approval_id = :approval_id AND approval_level = 2";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':status' => $data['status'],
                ':comments' => $data['comments'] ?? null,
                ':approval_id' => $data['approval_id']
            ]);

            // Update curriculum status if approved
            if ($data['status'] === 'Approved') {
                $query = "
                    UPDATE curricula 
                    SET status = 'Active' 
                    WHERE curriculum_id = (
                        SELECT curriculum_id 
                        FROM curriculum_approvals 
                        WHERE approval_id = :approval_id
                    )";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':approval_id' => $data['approval_id']]);
            }

            header('Location: /dean/curriculum?success=Curriculum approval processed');
        } catch (PDOException $e) {
            error_log("Error processing curriculum approval: " . $e->getMessage());
            header('Location: /dean/curriculum?error=Failed to process curriculum approval');
        }
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
                    header('Location: /dean/profile');
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
                    'doctorate_degree' => trim($_POST['dpost_doctorate_degree'] ?? ''),
                    'post_doctorate_degree' => trim($_POST['bachelor_degree'] ?? ''),
                    'advisory_class' => trim($_POST['advisory_class'] ?? ''),
                    'designation' => trim($_POST['designation'] ?? ''),
                    'expertise_level' => trim($_POST['expertise_level'] ?? ''),
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
                            // It's an error message
                            $errors[] = $profilePictureResult;
                        } else {
                            // It's a successful upload path
                            $profilePicturePath = $profilePictureResult;
                        }
                    }

                    // Handle user profile updates only if fields are provided or profile picture uploaded
                    if (
                        !empty($data['email']) || !empty($data['first_name']) || !empty($data['last_name']) ||
                        !empty($data['phone']) || !empty($data['username']) || !empty($data['suffix']) ||
                        !empty($data['title']) || $profilePicturePath
                    ) {
                        // Validate required fields only if they are being updated
                        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $errors[] = 'Valid email is required.';
                        }
                        if (!empty($data['phone']) && !preg_match('/^\d{10,12}$/', $data['phone'])) {
                            $errors[] = 'Phone number must be 10-12 digits.';
                        }
                        // And add this after your existing foreach loop for validFields:
                        if ($profilePicturePath) {
                            $setClause[] = "`profile_picture` = :profile_picture";
                            $params[":profile_picture"] = $profilePicturePath;
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

                            // Add profile picture to update if uploaded
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
                                    if (!empty($data['expertise_level']) && !empty($data['course_id'])) {
                                        // Check if specialization already exists
                                        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM specializations WHERE faculty_id = :faculty_id AND course_id = :course_id");
                                        $checkStmt->execute([':faculty_id' => $facultyId, ':course_id' => $data['course_id']]);
                                        $exists = $checkStmt->fetchColumn();

                                        if ($exists > 0) {
                                            $errors[] = 'You already have this specialization. Use edit to modify it.';
                                            break;
                                        }

                                        $insertSpecializationStmt = $this->db->prepare("
                                        INSERT INTO specializations (faculty_id, course_id, expertise_level, created_at)
                                        VALUES (:faculty_id, :course_id, :expertise_level, NOW())
                                    ");
                                        $specializationParams = [
                                            ':faculty_id' => $facultyId,
                                            ':course_id' => $data['course_id'],
                                            ':expertise_level' => $data['expertise_level'],
                                        ];
                                        error_log("profile: Add specialization query - " . $insertSpecializationStmt->queryString . ", Params: " . print_r($specializationParams, true));

                                        if (!$insertSpecializationStmt->execute($specializationParams)) {
                                            $errorInfo = $insertSpecializationStmt->errorInfo();
                                            error_log("profile: Add specialization failed - " . print_r($errorInfo, true));
                                            throw new Exception("Failed to add specialization");
                                        }
                                        error_log("profile: Successfully added specialization");
                                    } else {
                                        $errors[] = 'Course and expertise level are required to add specialization.';
                                    }
                                    break;

                                case 'remove_specialization':
                                    if (!empty($data['course_id'])) {
                                        error_log("profile: Attempting to remove specialization with course_id: " . $data['course_id'] . ", faculty_id: $facultyId");

                                        // First, check if the record exists
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

                                case 'update_specialization':
                                    if (!empty($data['course_id']) && !empty($data['expertise_level'])) {
                                        error_log("profile: Attempting to update specialization with course_id: " . $data['course_id'] . ", faculty_id: $facultyId");

                                        // Check if the record exists first
                                        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM specializations WHERE faculty_id = :faculty_id AND course_id = :course_id");
                                        $checkStmt->execute([':faculty_id' => $facultyId, ':course_id' => $data['course_id']]);
                                        $recordExists = $checkStmt->fetchColumn();

                                        if ($recordExists > 0) {
                                            $updateStmt = $this->db->prepare("UPDATE specializations SET expertise_level = :expertise_level, updated_at = NOW() WHERE faculty_id = :faculty_id AND course_id = :course_id");
                                            $updateParams = [
                                                ':faculty_id' => $facultyId,
                                                ':course_id' => $data['course_id'],
                                                ':expertise_level' => $data['expertise_level'],
                                            ];
                                            error_log("profile: Update specialization query - " . $updateStmt->queryString . ", Params: " . print_r($updateParams, true));

                                            if ($updateStmt->execute($updateParams)) {
                                                $affectedRows = $updateStmt->rowCount();
                                                error_log("profile: Successfully updated $affectedRows rows");
                                                if ($affectedRows === 0) {
                                                    error_log("profile: Warning - No rows were affected by update operation");
                                                    $errors[] = 'No changes were made to the specialization.';
                                                }
                                            } else {
                                                $errorInfo = $updateStmt->errorInfo();
                                                error_log("profile: Update failed - " . print_r($errorInfo, true));
                                                throw new Exception("Failed to update specialization: " . $errorInfo[2]);
                                            }
                                        } else {
                                            error_log("profile: No record found for update");
                                            $errors[] = 'Specialization not found for update.';
                                        }
                                    } else {
                                        $errors[] = 'Course ID and expertise level are required to update specialization.';
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

                        // Update profile picture in session if it was uploaded
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

                header('Location: /dean/profile');
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
                       s.expertise_level, 
                       (SELECT COUNT(*) FROM faculty f2 JOIN users fu ON f2.user_id = fu.user_id WHERE fu.department_id = u.department_id) as facultyCount,
                       (SELECT COUNT(DISTINCT sch.course_id) FROM schedules sch WHERE sch.faculty_id = f.faculty_id) as coursesCount,
                       (SELECT COUNT(*) FROM specializations s2 WHERE s2.course_id = c2.course_id) as specializationsCount,
                       (SELECT COUNT(*) FROM faculty_requests fr WHERE fr.department_id = u.department_id AND fr.status = 'pending') as pendingApplicantsCount,
                       (SELECT semester_name FROM semesters WHERE is_current = 1) as currentSemester,
                       (SELECT created_at FROM auth_logs WHERE user_id = u.user_id AND action = 'login_success' ORDER BY created_at DESC LIMIT 1) as lastLogin
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                LEFT JOIN courses c2 ON d.department_id = c2.department_id
                LEFT JOIN schedules sch ON c2.course_id = sch.course_id
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN faculty f ON u.user_id = f.user_id
                LEFT JOIN specializations s ON f.faculty_id = s.faculty_id
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

            require_once __DIR__ . '/../views/dean/profile.php';
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
                'role_name' => 'Program dean',
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
            require_once __DIR__ . '/../views/dean/profile.php';
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

    public function settings()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("settings: No user_id in session");
            http_response_code(401);
            return ['error' => 'No user session'];
        }

        $collegeId = $this->getDeanCollegeId($userId);
        if (!$collegeId) {
            error_log("settings: No college found for dean user_id: $userId");
            http_response_code(403);
            return ['error' => 'No college assigned to this dean'];
        }

        $controller = $this;
        $error = null;
        $success = null;

        try {
            // Generate CSRF token
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            // Handle POST actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate CSRF token
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    error_log("settings: Invalid CSRF token");
                    $error = "Invalid request";
                    http_response_code(403);
                } else {
                    if (isset($_POST['update_settings'])) {
                        $result = $this->updateSettings($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    } elseif (isset($_POST['add_department'])) {
                        $result = $this->addDepartment($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    } elseif (isset($_POST['edit_department'])) {
                        $result = $this->editDepartment($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    } elseif (isset($_POST['delete_department'])) {
                        $result = $this->deleteDepartment($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    } elseif (isset($_POST['add_program'])) {
                        $result = $this->addProgram($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    } elseif (isset($_POST['edit_program'])) {
                        $result = $this->editProgram($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    } elseif (isset($_POST['delete_program'])) {
                        $result = $this->deleteProgram($_POST, $collegeId);
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        } else {
                            $success = $result['success'];
                        }
                    }
                }
            }

            // Fetch college details
            $query = "SELECT college_name, logo_path FROM colleges WHERE college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            $college = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['college_name' => '', 'logo_path' => null];

            // Fetch departments
            $query = "SELECT department_id, department_name FROM departments WHERE college_id = :college_id ORDER BY department_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch programs
            $query = "
                SELECT p.program_id, p.program_code, p.program_name, p.department_id, d.department_name
                FROM programs p
                JOIN departments d ON p.department_id = d.department_id
                WHERE d.college_id = :college_id AND p.is_active = 1
                ORDER BY d.department_name, p.program_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Load settings view
            require_once __DIR__ . '/../views/dean/settings.php';
        } catch (PDOException $e) {
            error_log("settings: PDO Error - " . $e->getMessage());
            $error = "Database error occurred";
            http_response_code(500);
            require_once __DIR__ . '/../views/dean/settings.php';
        } catch (Exception $e) {
            error_log("settings: Error - " . $e->getMessage());
            $error = $e->getMessage();
            http_response_code(500);
            require_once __DIR__ . '/../views/dean/settings.php';
        }
    }

    private function updateSettings($data, $collegeId)
    {
        try {
            // Validate college name
            $collegeName = trim($data['college_name'] ?? '');
            if (empty($collegeName) || strlen($collegeName) > 100) {
                error_log("Invalid college name provided");
                return ['error' => 'College name must be 1-100 characters'];
            }

            // Handle logo upload
            $logoPath = null;
            if (isset($_FILES['college_logo']) && $_FILES['college_logo']['error'] != UPLOAD_ERR_NO_FILE) {
                $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                $fileType = $_FILES['college_logo']['type'];
                $fileSize = $_FILES['college_logo']['size'];

                if (!in_array($fileType, $allowedTypes)) {
                    error_log("Invalid file type for college logo");
                    return ['error' => 'Invalid file type. Use PNG, JPEG, or GIF'];
                }

                if ($fileSize > $maxSize) {
                    error_log("College logo file too large: $fileSize bytes");
                    return ['error' => 'File size exceeds 2MB limit'];
                }

                $uploadDir = __DIR__ . '/../public/uploads/colleges/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = 'college_' . $collegeId . '_' . time() . '.' . pathinfo($_FILES['college_logo']['name'], PATHINFO_EXTENSION);
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['college_logo']['tmp_name'], $uploadPath)) {
                    $logoPath = '/uploads/colleges/' . $fileName;
                } else {
                    error_log("Failed to move uploaded college logo");
                    return ['error' => 'Failed to upload logo'];
                }
            }

            // Update college details
            $query = "
                UPDATE colleges 
                SET college_name = :college_name" . ($logoPath ? ", logo_path = :logo_path" : "") . "
                WHERE college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $params = [
                ':college_name' => $collegeName,
                ':college_id' => $collegeId
            ];
            if ($logoPath) {
                $params[':logo_path'] = $logoPath;
            }
            $stmt->execute($params);

            return ['success' => 'Settings updated successfully'];
        } catch (PDOException $e) {
            error_log("updateSettings: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to update settings'];
        }
    }

    private function addDepartment($data, $collegeId)
    {
        try {
            $departmentName = trim($data['department_name'] ?? '');
            if (empty($departmentName) || strlen($departmentName) > 100) {
                error_log("Invalid department name provided");
                return ['error' => 'Department name must be 1-100 characters'];
            }

            // Check if department exists
            $query = "SELECT COUNT(*) FROM departments WHERE department_name = :department_name AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_name' => $departmentName, ':college_id' => $collegeId]);
            if ($stmt->fetchColumn() > 0) {
                error_log("Department already exists: $departmentName");
                return ['error' => 'Department already exists'];
            }

            // Insert department
            $query = "INSERT INTO departments (department_name, college_id) VALUES (:department_name, :college_id)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_name' => $departmentName, ':college_id' => $collegeId]);

            return ['success' => 'Department added successfully'];
        } catch (PDOException $e) {
            error_log("addDepartment: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to add department'];
        }
    }

    private function editDepartment($data, $collegeId)
    {
        try {
            $departmentId = intval($data['department_id'] ?? 0);
            $departmentName = trim($data['department_name'] ?? '');
            if (empty($departmentName) || strlen($departmentName) > 100) {
                error_log("Invalid department name provided");
                return ['error' => 'Department name must be 1-100 characters'];
            }

            // Verify department belongs to college
            $query = "SELECT COUNT(*) FROM departments WHERE department_id = :department_id AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_id' => $departmentId, ':college_id' => $collegeId]);
            if ($stmt->fetchColumn() == 0) {
                error_log("Department not found or unauthorized: $departmentId");
                return ['error' => 'Department not found'];
            }

            // Update department
            $query = "UPDATE departments SET department_name = :department_name WHERE department_id = :department_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_name' => $departmentName, ':department_id' => $departmentId]);

            return ['success' => 'Department updated successfully'];
        } catch (PDOException $e) {
            error_log("editDepartment: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to update department'];
        }
    }

    private function deleteDepartment($data, $collegeId)
    {
        try {
            $departmentId = intval($data['department_id'] ?? 0);

            // Verify department belongs to college
            $query = "SELECT COUNT(*) FROM departments WHERE department_id = :department_id AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_id' => $departmentId, ':college_id' => $collegeId]);
            if ($stmt->fetchColumn() == 0) {
                error_log("Department not found or unauthorized: $departmentId");
                return ['error' => 'Department not found'];
            }

            // Check for dependent programs
            $query = "SELECT COUNT(*) FROM programs WHERE department_id = :department_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_id' => $departmentId]);
            if ($stmt->fetchColumn() > 0) {
                error_log("Cannot delete department with programs: $departmentId");
                return ['error' => 'Cannot delete department with associated programs'];
            }

            // Delete department
            $query = "DELETE FROM departments WHERE department_id = :department_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_id' => $departmentId]);

            return ['success' => 'Department deleted successfully'];
        } catch (PDOException $e) {
            error_log("deleteDepartment: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to delete department'];
        }
    }

    private function addProgram($data, $collegeId)
    {
        try {
            $programName = trim($data['program_name'] ?? '');
            $departmentId = intval($data['department_id'] ?? 0);
            if (empty($programName) || strlen($programName) > 100) {
                error_log("Invalid program name provided");
                return ['error' => 'Program name must be 1-100 characters'];
            }
            if ($departmentId <= 0) {
                error_log("Invalid department ID provided");
                return ['error' => 'Invalid department selected'];
            }
            $programCode = trim($data['program_code'] ?? '');

            // Verify department belongs to college
            $query = "SELECT COUNT(*) FROM departments WHERE department_id = :department_id AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_id' => $departmentId, ':college_id' => $collegeId]);
            if ($stmt->fetchColumn() == 0) {
                error_log("Department not found or unauthorized: $departmentId");
                return ['error' => 'Department not found'];
            }

            // Check if program exists
            $query = "SELECT COUNT(*) FROM programs WHERE program_code = :program_code, program_name = :program_name AND department_id = :department_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':program_name' => $programName, ':department_id' => $departmentId]);
            if ($stmt->fetchColumn() > 0) {
                error_log("Program already exists: $programName");
                return ['error' => 'Program already exists in this department'];
            }

            // Insert program
            $query = "INSERT INTO programs (program_code, program_name, department_id, is_active) VALUES (program_code: :program_name, :department_id, 1)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':program_name' => $programName, ':department_id' => $departmentId]);

            return ['success' => 'Program added successfully'];
        } catch (PDOException $e) {
            error_log("addProgram: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to add program'];
        }
    }

    private function editProgram($data, $collegeId)
    {
        try {
            $programId = intval($data['program_id'] ?? 0);
            $programName = trim($data['program_name'] ?? '');
            $programCode = trim($data['program_code'] ?? '');
            $departmentId = intval($data['department_id'] ?? 0);
            if (empty($programName) || strlen($programName) > 100) {
                error_log("Invalid program name provided");
                return ['error' => 'Program name must be 1-100 characters'];
            }
            if ($departmentId <= 0) {
                error_log("Invalid department ID provided");
                return ['error' => 'Invalid department selected'];
            }

            // Verify program and department
            $query = "
                SELECT COUNT(*) 
                FROM programs p
                JOIN departments d ON p.department_id = d.department_id
                WHERE p.program_id = :program_id AND d.college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':program_id' => $programId, ':college_id' => $collegeId]);
            if ($stmt->fetchColumn() == 0) {
                error_log("Program not found or unauthorized: $programId");
                return ['error' => 'Program not found'];
            }

            // Update program
            $query = "UPDATE programs SET program_code = program_code: program_name = :program_name, department_id = :department_id WHERE program_id = :program_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':program_code' => $programCode,
                ':program_name' => $programName,
                ':department_id' => $departmentId,
                ':program_id' => $programId
            ]);

            return ['success' => 'Program updated successfully'];
        } catch (PDOException $e) {
            error_log("editProgram: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to update program'];
        }
    }

    private function deleteProgram($data, $collegeId)
    {
        try {
            $programId = intval($data['program_id'] ?? 0);

            // Verify program
            $query = "
                SELECT COUNT(*) 
                FROM programs p
                JOIN departments d ON p.department_id = d.department_id
                WHERE p.program_id = :program_id AND d.college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':program_id' => $programId, ':college_id' => $collegeId]);
            if ($stmt->fetchColumn() == 0) {
                error_log("Program not found or unauthorized: $programId");
                return ['error' => 'Program not found'];
            }

            // Delete program
            $query = "DELETE FROM programs WHERE program_id = :program_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':program_id' => $programId]);

            return ['success' => 'Program deleted successfully'];
        } catch (PDOException $e) {
            error_log("deleteProgram: PDO Error - " . $e->getMessage());
            return ['error' => 'Failed to delete program'];
        }
    }

    public function getDeanCollegeId($userId)
    {
        $query = "SELECT college_id FROM deans WHERE user_id = :user_id AND is_current = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['college_id'] ?? null;
    }
}
