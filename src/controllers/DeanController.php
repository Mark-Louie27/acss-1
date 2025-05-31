<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';

class DeanController
{
    private $db;
    private $userModel;

    public function __construct()
    {
        error_log("DeanController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in DeanController");
            die("Database connection failed. Please try again later.");
        }
        $this->userModel = new UserModel();
        $this->restrictToDean();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

    public function dashboard()
    {
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($userId);

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

        // Pass semester and schedules to the view
        $currentSemester = $currentSemesterDisplay;
        require_once __DIR__ . '/../views/dean/dashboard.php';
    }

    private function getCollegeStats($collegeId, $type)
    {
        try {
            $query = "";
            switch ($type) {
                case 'faculty':
                    $query = "
                        SELECT COUNT(*) 
                        FROM faculty f 
                        JOIN departments d ON f.department_id = d.department_id 
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
            $programChairs = [];
            $faculty = [];
            $requests = [];
            $departments = [];
            $currentSemester = ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            $error = null;

            // Fetch Program Chairs
            $queryChairs = "
            SELECT u.*, pc.program_id, p.program_name, d.department_name, d.department_id
            FROM users u
            JOIN faculty f ON u.user_id = f.user_id
            JOIN program_chairs pc ON f.faculty_id = pc.faculty_id
            JOIN programs p ON pc.program_id = p.program_id
            JOIN departments d ON p.department_id = d.department_id
            WHERE d.college_id = :college_id AND pc.is_current = 1 AND u.is_active = 1
            ORDER BY u.last_name, u.first_name";
            $stmtChairs = $this->db->prepare($queryChairs);
            if (!$stmtChairs) {
                throw new PDOException("Failed to prepare queryChairs: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtChairs->execute([':college_id' => $collegeId]);
            $programChairs = $stmtChairs->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Faculty (including those with schedules in the college)
            $queryFaculty = "
            SELECT DISTINCT u.*, f.academic_rank, f.employment_type, d.department_name, d.department_id
            FROM users u
            JOIN faculty f ON u.user_id = f.user_id
            JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN schedules s ON f.faculty_id = s.faculty_id
            LEFT JOIN courses c ON s.course_id = c.course_id
            WHERE (d.college_id = :college_id1 OR c.department_id IN (
                SELECT department_id FROM departments WHERE college_id = :college_id2
            )) AND u.is_active = 1
            ORDER BY u.last_name, u.first_name";
            $stmtFaculty = $this->db->prepare($queryFaculty);
            if (!$stmtFaculty) {
                throw new PDOException("Failed to prepare queryFaculty: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtFaculty->execute([':college_id1' => $collegeId, ':college_id2' => $collegeId]);
            $faculty = $stmtFaculty->fetchAll(PDO::FETCH_ASSOC);

            // Fetch pending faculty requests
            $queryRequests = "
            SELECT fr.*, d.department_name
            FROM faculty_requests fr
            JOIN departments d ON fr.department_id = d.department_id
            WHERE fr.college_id = :college_id AND fr.status = 'pending'
            ORDER BY fr.created_at";
            $stmtRequests = $this->db->prepare($queryRequests);
            if (!$stmtRequests) {
                throw new PDOException("Failed to prepare queryRequests: " . implode(', ', $this->db->errorInfo()));
            }
            $stmtRequests->execute([':college_id' => $collegeId]);
            $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);

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

            // Handle POST actions (accept, reject, deactivate)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['action'], $_POST['user_id']) && in_array($_POST['action'], ['accept', 'reject', 'deactivate'])) {
                    $this->handleUserAction($_POST);
                } elseif (isset($_POST['request_id']) && is_numeric($_POST['request_id'])) {
                    $this->handleFacultyRequest($_POST);
                } else {
                    error_log("faculty: Invalid POST data");
                    $error = "Invalid request data";
                }
            }

            // Load faculty management view
            require_once __DIR__ . '/../views/dean/faculty.php';
        } catch (PDOException $e) {
            error_log("faculty: PDO Error - " . $e->getMessage());
            http_response_code(500);
            $error = "Database error: " . $e->getMessage();
            $programChairs = $faculty = $requests = $departments = [];
            $currentSemester = ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            require_once __DIR__ . '/../views/dean/faculty.php';
        } catch (Exception $e) {
            error_log("faculty: Error - " . $e->getMessage());
            http_response_code(500);
            $error = $e->getMessage();
            $programChairs = $faculty = $requests = $departments = [];
            $currentSemester = ['semester_name' => 'N/A', 'academic_year' => 'N/A'];
            require_once __DIR__ . '/../views/dean/faculty.php';
        }
    }

    private function handleUserAction($data)
    {
        $userId = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        $action = $data['action'];
        $collegeId = $this->getDeanCollegeId($_SESSION['user_id']);

        if (!$userId || !$collegeId) {
            $_SESSION['error'] = 'Invalid user or college.';
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'deactivate') {
                $query = "UPDATE users SET is_active = 0 WHERE user_id = :user_id AND college_id = :college_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':user_id' => $userId, ':college_id' => $collegeId]);
                $_SESSION['success'] = 'User account deactivated successfully.';
            } elseif ($action === 'activate') {
                $query = "UPDATE users SET is_active = 1 WHERE user_id = :user_id AND college_id = :college_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':user_id' => $userId, ':college_id' => $collegeId]);
                $_SESSION['success'] = 'User account activated successfully.';
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error handling user action: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while processing the action.';
        }
    }

    private function handleFacultyRequest($data)
    {
        $requestId = filter_var($data['request_id'], FILTER_VALIDATE_INT);
        $action = $data['action'] ?? '';
        $collegeId = $this->getDeanCollegeId($_SESSION['user_id']);

        if (!$requestId || !$collegeId) {
            $_SESSION['error'] = 'Invalid request or college.';
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'accept') {
                // Fetch request details
                $query = "SELECT * FROM faculty_requests WHERE request_id = :request_id AND college_id = :college_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':request_id' => $requestId, ':college_id' => $collegeId]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($request) {
                    // Insert into users table
                    $query = "
                        INSERT INTO users (employee_id, username, password_hash, email, first_name, middle_name, last_name, suffix, role_id, department_id, college_id)
                        VALUES (:employee_id, :username, :password_hash, :email, :first_name, :middle_name, :last_name, :suffix, :role_id, :department_id, :college_id)";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':employee_id' => $request['employee_id'],
                        ':username' => $request['username'],
                        ':password_hash' => $request['password_hash'],
                        ':email' => $request['email'],
                        ':first_name' => $request['first_name'],
                        ':middle_name' => $request['middle_name'],
                        ':last_name' => $request['last_name'],
                        ':suffix' => $request['suffix'],
                        ':role_id' => 6, // Faculty role
                        ':department_id' => $request['department_id'],
                        ':college_id' => $request['college_id']
                    ]);
                    $userId = $this->db->lastInsertId();

                    // Insert into faculty table
                    $query = "
                        INSERT INTO faculty (user_id, employee_id, academic_rank, employment_type, department_id)
                        VALUES (:user_id, :employee_id, :academic_rank, :employment_type, :department_id)";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':employee_id' => $request['employee_id'],
                        ':academic_rank' => $request['academic_rank'],
                        ':employment_type' => $request['employment_type'],
                        ':department_id' => $request['department_id']
                    ]);

                    // Update request status
                    $query = "UPDATE faculty_requests SET status = 'approved' WHERE request_id = :request_id";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([':request_id' => $requestId]);

                    $_SESSION['success'] = 'Faculty request approved successfully.';
                }
            } elseif ($action === 'reject') {
                $query = "UPDATE faculty_requests SET status = 'rejected' WHERE request_id = :request_id AND college_id = :college_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':request_id' => $requestId, ':college_id' => $collegeId]);
                $_SESSION['success'] = 'Faculty request rejected successfully.';
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error handling faculty request: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while processing the request.';
        }
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
                c.year_level,
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

            if ($yearLevelFilter) {
                $query .= " AND c.year_level = :year_level";
                $countQuery .= " AND c.year_level = :year_level";
                $params[':year_level'] = $yearLevelFilter;
                $countParams[':year_level'] = $yearLevelFilter;
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
        $userId = $_SESSION['user_id'];
        $collegeId = $this->getDeanCollegeId($userId);

        // Fetch user data
        $query = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $controller = $this;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateProfile($_POST, $userId, $collegeId);
        }

        // Load profile view
        require_once __DIR__ . '/../views/dean/profile.php';
    }

    private function updateProfile($data, $userId, $collegeId)
    {
        try {
            // Validate department_id belongs to the dean's college
            $query = "SELECT department_id FROM departments WHERE department_id = :department_id AND college_id = :college_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $data['department_id'],
                ':college_id' => $collegeId
            ]);
            if (!$stmt->fetch()) {
                header('Location: /dean/profile?error=Invalid department selected');
                exit;
            }

            $updateData = [
                'employee_id' => $data['employee_id'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'suffix' => $data['suffix'] ?? null,
                'profile_picture' => $this->handleProfilePictureUpload() ?? null,
                'role_id' => 4, // Dean role
                'department_id' => $data['department_id'],
                'college_id' => $collegeId, // Use the dean's assigned college
                'is_active' => 1
            ];

            // Remove profile_picture from update if no new file was uploaded
            if ($updateData['profile_picture'] === null) {
                unset($updateData['profile_picture']);
            }

            if ($this->userModel->updateUser($userId, $updateData)) {
                header('Location: /dean/profile?success=Profile updated successfully');
            } else {
                header('Location: /dean/profile?error=Failed to update profile');
            }
        } catch (Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            header('Location: /dean/profile?error=Failed to update profile');
        }
    }

    private function handleProfilePictureUpload()
    {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] == UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
            return '/uploads/profiles/' . $fileName;
        }

        return null;
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
            SELECT p.program_id, p.program_name, p.department_id, d.department_name
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

        // Verify department belongs to college
        $query = "SELECT COUNT(*) FROM departments WHERE department_id = :department_id AND college_id = :college_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':department_id' => $departmentId, ':college_id' => $collegeId]);
        if ($stmt->fetchColumn() == 0) {
            error_log("Department not found or unauthorized: $departmentId");
            return ['error' => 'Department not found'];
        }

        // Check if program exists
        $query = "SELECT COUNT(*) FROM programs WHERE program_name = :program_name AND department_id = :department_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':program_name' => $programName, ':department_id' => $departmentId]);
        if ($stmt->fetchColumn() > 0) {
            error_log("Program already exists: $programName");
            return ['error' => 'Program already exists in this department'];
        }

        // Insert program
        $query = "INSERT INTO programs (program_name, department_id, is_active) VALUES (:program_name, :department_id, 1)";
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
        $query = "UPDATE programs SET program_name = :program_name, department_id = :department_id WHERE program_id = :program_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
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
