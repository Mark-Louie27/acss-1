<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/EmailService.php';
class AdminController
{
    public $db;
    private $authService;
    private $emailService;

    public function __construct()  // Remove the $db parameter since we're not using it
    {
        error_log("AdminController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in AdminController");
            die("Database connection failed. Please try again later.");
        }

        $this->emailService = new EmailService();
        $this->authService = new AuthService($this->db);
        $this->restrictToAdmin();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function restrictToAdmin()
    {
        error_log("restrictToAdmin: Checking session - user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role_id: " . ($_SESSION['role_id'] ?? 'none'));
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
            error_log("restrictToAdmin: Redirecting to login due to unauthorized access");
            header('Location: /login?error=Unauthorized access');
            exit;
        }
    }

    public function dashboard()
    {
        try {
            // Fetch stats
            $userCount = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $collegeCount = $this->db->query("SELECT COUNT(*) FROM colleges")->fetchColumn();
            $departmentCount = $this->db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
            $facultyCount = $this->db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
            $scheduleCount = $this->db->query("SELECT COUNT(*) FROM schedules")->fetchColumn();

            // Get department name
            $deptStmt = $this->db->prepare("SELECT department_name FROM departments WHERE department_id = :department_id");
            $deptStmt->execute([':department_id' => $_SESSION['user_id']]);
            $departmentName = $deptStmt->fetchColumn();

            // Fetch current semester
            $currentSemesterStmt = $this->db->query("SELECT semester_name, academic_year FROM semesters WHERE is_current = 1 LIMIT 1");
            $currentSemester = $currentSemesterStmt->fetch(PDO::FETCH_ASSOC);
            $semesterInfo = $currentSemester ? "{$currentSemester['semester_name']} {$currentSemester['academic_year']}" : '2nd Semester 2024-2025';

            // Fetch all available semesters
            $semestersStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters ORDER BY start_date DESC");
            $semesters = $semestersStmt->fetchAll(PDO::FETCH_ASSOC);

            // Handle semester set form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_semester'])) {
                $semesterName = filter_input(INPUT_POST, 'semester_name', FILTER_SANITIZE_STRING);
                $academicYear = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_STRING);

                if (in_array($semesterName, ['1st', '2nd', 'Summer']) && preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
                    list($yearStart, $yearEnd) = explode('-', $academicYear);
                    $startDate = "$yearStart-06-01"; // Assuming June 1st as start date for simplicity
                    $endDate = "$yearEnd-05-31";     // Assuming May 31st as end date for simplicity

                    // Deactivate all semesters
                    $this->db->exec("UPDATE semesters SET is_current = 0");

                    // Check if semester exists, update or insert
                    $checkStmt = $this->db->prepare("SELECT semester_id FROM semesters WHERE semester_name = :semester_name AND academic_year = :academic_year");
                    $checkStmt->execute([':semester_name' => $semesterName, ':academic_year' => $academicYear]);
                    $existingSemester = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingSemester) {
                        $updateStmt = $this->db->prepare("UPDATE semesters SET is_current = 1, start_date = :start_date, end_date = :end_date WHERE semester_id = :semester_id");
                        $updateStmt->execute([
                            ':semester_id' => $existingSemester['semester_id'],
                            ':start_date' => $startDate,
                            ':end_date' => $endDate
                        ]);
                    } else {
                        $insertStmt = $this->db->prepare("INSERT INTO semesters (semester_name, academic_year, year_start, year_end, start_date, end_date, is_current) VALUES (:semester_name, :academic_year, :year_start, :year_end, :start_date, :end_date, 1)");
                        $insertStmt->execute([
                            ':semester_name' => $semesterName,
                            ':academic_year' => $academicYear,
                            ':year_start' => $yearStart,
                            ':year_end' => $yearEnd,
                            ':start_date' => $startDate,
                            ':end_date' => $endDate
                        ]);
                    }

                    $_SESSION['success'] = 'Semester updated successfully.';
                    header('Location: /admin/dashboard');
                    exit;
                } else {
                    $_SESSION['error'] = 'Invalid semester or year format. Use YYYY-YYYY (e.g., 2024-2025).';
                }
            }

            $controller = $this;
            require_once __DIR__ . '/../views/admin/dashboard.php';
        } catch (PDOException $e) {
            error_log("Dashboard error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    public function activityLogs()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT al.log_id, al.action_type, al.action_description, al.entity_type, al.entity_id, 
                       al.created_at, u.first_name, u.last_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.user_id
                ORDER BY al.created_at DESC
                LIMIT 50
            ");
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $controller = $this;
            require_once __DIR__ . '/../views/admin/act_logs.php';
        } catch (PDOException $e) {
            error_log("Activity logs error: " . $e->getMessage());
            $_SESSION['error'] = "Server error";
            header('Location: /admin/dashboard');
            exit;
        }
    }

    public function manageUsers()
    {
        try {
            if (!$this->authService->isLoggedIn() || $_SESSION['role_id'] !== 1) { // Assuming role_id 1 is admin
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unauthorized access'];
                header('Location: /login');
                exit;
            }

            $action = $_GET['action'] ?? 'list';
            $userId = $_GET['user_id'] ?? null;
            $csrfToken = $this->authService->generateCsrfToken();

            // Fetch common data
            $usersStmt = $this->db->query("
            SELECT u.user_id, u.username, u.first_name, u.middle_name, u.last_name, u.suffix, u.is_active, u.email,
                   r.role_name, c.college_name, d.department_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN departments d ON u.department_id = d.department_id
            ORDER BY u.first_name, u.last_name
        ");
            $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

            $roles = $this->db->query("SELECT role_id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
            $colleges = $this->db->query("SELECT college_id, college_name FROM colleges ORDER BY college_name")->fetchAll(PDO::FETCH_ASSOC);
            $departments = $this->db->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token'];
                    header('Location: /admin/users');
                    exit;
                }

                $this->db->beginTransaction();

                try {
                    if ($action === 'create') {
                        $username = $_POST['username'] ?? '';
                        $password = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);
                        $first_name = $_POST['first_name'] ?? '';
                        $last_name = $_POST['last_name'] ?? '';
                        $role_id = $_POST['role_id'] ?? null;
                        $college_id = $_POST['college_id'] ?: null;
                        $department_id = $_POST['department_id'] ?: null;
                        $email = $_POST['email'] ?? '';

                        $stmt = $this->db->prepare("
                        INSERT INTO users (username, password, first_name, last_name, role_id, college_id, department_id, email, is_active, created_at)
                        VALUES (:username, :password, :first_name, :last_name, :role_id, :college_id, :department_id, :email, 0, NOW())
                    ");
                        $stmt->execute([
                            ':username' => $username,
                            ':password' => $password,
                            ':first_name' => $first_name,
                            ':last_name' => $last_name,
                            ':role_id' => $role_id,
                            ':college_id' => $college_id,
                            ':department_id' => $department_id,
                            ':email' => $email,
                            ':is_active' => 0 // All users are inactive until approved
                        ]);

                        $newUserId = $this->db->lastInsertId();
                        $fullName = "$first_name $last_name";
                        $roleName = $roles[$role_id - 1]['role_name'] ?? 'Unknown Role';

                        // Send confirmation email to the new user
                        $this->emailService->sendConfirmationEmail($email, $fullName, $roleName);

                        $adminEmail = 'admin@example.com'; // Replace with actual admin email
                        $this->emailService->sendApprovalEmail($adminEmail, $fullName, $roleName);
                    } elseif ($action === 'update' && $userId) {
                        $username = $_POST['username'] ?? '';
                        $first_name = $_POST['first_name'] ?? '';
                        $last_name = $_POST['last_name'] ?? '';
                        $role_id = $_POST['role_id'] ?? null;
                        $college_id = $_POST['college_id'] ?: null;
                        $department_id = $_POST['department_id'] ?: null;
                        $email = $_POST['email'] ?? '';

                        $updateFields = [
                            'username = :username',
                            'first_name = :first_name',
                            'last_name = :last_name',
                            'role_id = :role_id',
                            'college_id = :college_id',
                            'department_id = :department_id',
                            'email = :email',
                            'updated_at = NOW()'
                        ];
                        $params = [
                            ':user_id' => $userId,
                            ':username' => $username,
                            ':first_name' => $first_name,
                            ':last_name' => $last_name,
                            ':role_id' => $role_id,
                            ':college_id' => $college_id,
                            ':department_id' => $department_id,
                            ':email' => $email
                        ];

                        if (!empty($_POST['password'])) {
                            $updateFields[] = 'password = :password';
                            $params[':password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        }

                        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = :user_id";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                    } elseif ($action === 'approve' && $userId) {
                        $checkStmt = $this->db->prepare("SELECT email, first_name, last_name, role_id FROM users WHERE user_id = :user_id");
                        $checkStmt->execute([':user_id' => $userId]);
                        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

                        if ($user) {
                            $stmt = $this->db->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE user_id = :user_id");
                            $stmt->execute([':user_id' => $userId]);
                            $roleName = $roles[$user['role_id'] - 1]['role_name'] ?? 'Unknown Role';
                            $this->emailService->sendApprovalEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], $roleName);
                            error_log("User approved: ID {$userId}, Email: {$user['email']} by admin ID: " . ($_SESSION['user_id'] ?? 'unknown'));
                        }
                    } elseif ($action === 'disable' && $userId) {
                        if ($_SESSION['user_id'] == $userId) {
                            echo json_encode(['success' => false, 'message' => 'Cannot disable your own account']);
                            exit;
                        }
                        $checkStmt = $this->db->prepare("SELECT user_id, username FROM users WHERE user_id = :user_id");
                        $checkStmt->execute([':user_id' => $userId]);
                        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

                        if ($user) {
                            $stmt = $this->db->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE user_id = :user_id");
                            $stmt->execute([':user_id' => $userId]);
                            error_log("User disabled: ID {$userId}, Username: {$user['username']} by admin ID: " . ($_SESSION['user_id'] ?? 'unknown'));
                        }
                    } elseif ($action === 'enable' && $userId) {
                        $checkStmt = $this->db->prepare("SELECT user_id, username FROM users WHERE user_id = :user_id");
                        $checkStmt->execute([':user_id' => $userId]);
                        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

                        if ($user) {
                            $stmt = $this->db->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE user_id = :user_id");
                            $stmt->execute([':user_id' => $userId]);
                            error_log("User enabled: ID {$userId}, Username: {$user['username']} by admin ID: " . ($_SESSION['user_id'] ?? 'unknown'));
                        }
                    }

                    $this->db->commit();
                    $_SESSION['flash'] = ['type' => 'success', 'message' => ucfirst($action) . ' user ' . ($action === 'create' ? 'created' : ($action === 'approve' ? 'approved' : 'updated')) . ' successfully'];

                    if (in_array($action, ['disable', 'enable', 'approve'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => ucfirst($action) . 'd successfully']);
                        exit;
                    }
                    header('Location: /admin/users');
                    exit;
                } catch (PDOException $e) {
                    $this->db->rollBack();
                    error_log("User action error ($action): " . $e->getMessage());
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to ' . $action . ' user'];
                    if (in_array($action, ['disable', 'enable', 'approve'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                        exit;
                    }
                    header('Location: /admin/users');
                    exit;
                }
            }

            $userDetails = null;
            if (in_array($action, ['view', 'edit']) && $userId) {
                $stmt = $this->db->prepare("
                SELECT u.*, r.role_name, c.college_name, d.department_name
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.user_id = :user_id
            ");
                $stmt->execute([':user_id' => $userId]);
                $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$userDetails) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User not found'];
                    header('Location: /admin/users');
                    exit;
                }

                // Return JSON for view action
                if ($action === 'view') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'user' => $userDetails]);
                    exit;
                }
            }

            $controller = $this;
            require_once __DIR__ . '/../views/admin/users.php';
        } catch (PDOException $e) {
            error_log("Manage users error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    public function collegesDepartments()
    {
        try {
            $collegesStmt = $this->db->query("SELECT college_id, college_name, college_code FROM colleges");
            $colleges = $collegesStmt->fetchAll(PDO::FETCH_ASSOC);

            $departmentsStmt = $this->db->query("
                SELECT d.department_id, d.department_name, c.college_name
                FROM departments d
                JOIN colleges c ON d.college_id = c.college_id
            ");
            $departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);

            $controller = $this;
            require_once __DIR__ . '/../views/admin/colleges_departments.php';
        } catch (PDOException $e) {
            error_log("Colleges/Departments error: " . $e->getMessage());
            $_SESSION['error'] = "Server error";
            header('Location: /admin/dashboard');
            exit;
        }
    }

    public function createCollegeDepartment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }

        try {
            $type = $_POST['type'] ?? '';
            if ($type === 'college') {
                $college_name = trim($_POST['college_name'] ?? '');
                $college_code = trim($_POST['college_code'] ?? '');

                if (empty($college_name) || empty($college_code)) {
                    $_SESSION['error'] = "College name and code are required";
                    header('Location: /admin/colleges');
                    exit;
                }

                $stmt = $this->db->prepare("SELECT COUNT(*) FROM colleges WHERE college_code = :college_code");
                $stmt->execute([':college_code' => $college_code]);
                if ($stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "College code already exists";
                    header('Location: /admin/colleges');
                    exit;
                }

                $stmt = $this->db->prepare("
                    INSERT INTO colleges (college_name, college_code)
                    VALUES (:college_name, :college_code)
                ");
                $stmt->execute([
                    ':college_name' => $college_name,
                    ':college_code' => $college_code
                ]);

                $_SESSION['success'] = "College created successfully";
            } elseif ($type === 'department') {
                $department_name = trim($_POST['department_name'] ?? '');
                $college_id = $_POST['college_id'] ?? null;

                if (empty($department_name) || empty($college_id)) {
                    $_SESSION['error'] = "Department name and college are required";
                    header('Location: /admin/colleges');
                    exit;
                }

                $stmt = $this->db->prepare("SELECT COUNT(*) FROM departments WHERE department_name = :department_name AND college_id = :college_id");
                $stmt->execute([':department_name' => $department_name, ':college_id' => $college_id]);
                if ($stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Department already exists in this college";
                    header('Location: /admin/colleges');
                    exit;
                }

                $stmt = $this->db->prepare("
                    INSERT INTO departments (department_name, college_id)
                    VALUES (:department_name, :college_id)
                ");
                $stmt->execute([
                    ':department_name' => $department_name,
                    ':college_id' => $college_id
                ]);

                $_SESSION['success'] = "Department created successfully";
            } else {
                $_SESSION['error'] = "Invalid request type";
                header('Location: /admin/colleges');
                exit;
            }

            header('Location: /admin/colleges');
            exit;
        } catch (PDOException $e) {
            error_log("Create college/department error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to create $type";
            header('Location: /admin/colleges');
            exit;
        }
    }

    public function faculty()
    {
        try {
            $stmt = $this->db->query("
                 SELECT 
                            f.faculty_id,
                            CONCAT(u.user_id, ' ', u.first_name, ' ', u.last_name) AS name
                        FROM faculty f
                        JOIN users u ON f.user_id = u.user_id
                        WHERE u.department_id = :department_id
                        ORDER BY u.last_name, u.first_name
            ");
            $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users = $this->db->query("SELECT user_id, first_name, last_name FROM users WHERE role_id = 3")->fetchAll(PDO::FETCH_ASSOC); // Assume role_id 3 = Faculty
            $colleges = $this->db->query("SELECT college_id, college_name FROM colleges")->fetchAll(PDO::FETCH_ASSOC);

            $controller = $this;
            require_once __DIR__ . '/../views/admin/faculty.php';
        } catch (PDOException $e) {
            error_log("Faculty error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    public function createFaculty()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $user_id = $_POST['user_id'] ?? null;
                $college_id = $_POST['college_id'] ?? null;

                $stmt = $this->db->prepare("
                    INSERT INTO faculty (user_id, college_id)
                    VALUES (:user_id, :college_id)
                ");
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':college_id' => $college_id
                ]);

                header('Location: /admin/faculty');
                exit;
            } catch (PDOException $e) {
                error_log("Create faculty error: " . $e->getMessage());
                http_response_code(500);
                echo "Failed to create faculty";
            }
        }
    }

    public function schedules()
    {
        try {
            $stmt = $this->db->query("
                SELECT s.schedule_id, c.course_name, u.first_name, u.last_name, cl.room_number, cl.building_name, s.day_of_week, s.start_time, s.end_time
                FROM schedules s
                JOIN courses c ON s.course_id = c.course_id
                JOIN faculty f ON s.faculty_id = f.faculty_id
                JOIN users u ON f.user_id = u.user_id
                JOIN classrooms cl ON s.classroom_id = cl.classroom_id
            ");
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $controller = $this;
            require_once __DIR__ . '/../views/admin/schedules.php';
        } catch (PDOException $e) {
            error_log("Schedules error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    /**
     * View/edit profile
     */
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
                error_log("profile: Received POST data - " . print_r($_POST, true));

                if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token'];
                    header('Location: /admin/profile');
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
                            error_log("profile: Profile picture upload error for user_id $userId: $profilePictureResult");
                        } else {
                            // It's a successful upload path
                            $profilePicturePath = $profilePictureResult;
                            error_log("profile: Profile picture upload successful for user_id $userId: $profilePicturePath");
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
                                error_log("profile: Users query - " . $userStmt->queryString . ", Params: " . print_r($params, true));
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
                        $facultyFields = ['academic_rank', 'employment_type', 'classification'];
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

                header('Location: /admin/profile');
                exit;
            }

            // GET request - Display profile
            $stmt = $this->db->prepare("
            SELECT u.*, d.department_name, c.college_name, r.role_name,
                   f.academic_rank, f.employment_type, f.classification
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
                       f.academic_rank, f.employment_type, f.classification,
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

            require_once __DIR__ . '/../views/admin/profile.php';
        } catch (Exception $e) {
            if (isset($this->db) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log("profile: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to load or update profile. Please try again.'];

            // Provide default user data in case of error
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
                'role_name' => 'admin',
                'academic_rank' => '',
                'employment_type' => '',
                'classification' => '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $specializations = [];
            require_once __DIR__ . '/../views/admin/profile.php';
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
                error_log("profile: Failed to create upload adminy: $uploadDir");
                return "Error: Failed to create upload adminy.";
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
        try {
            $stmt = $this->db->prepare("
                SELECT u.user_id, u.username, u.email, u.phone, u.first_name, u.middle_name, u.last_name, u.suffix, 
                       u.profile_picture
                FROM users u
                WHERE u.user_id = :user_id
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['error'] = "User not found";
                header('Location: /admin/dashboard');
                exit;
            }

            $colleges = $this->db->query("SELECT college_id, college_name FROM colleges")->fetchAll(PDO::FETCH_ASSOC);
            $departments = $this->db->query("SELECT department_id, department_name FROM departments")->fetchAll(PDO::FETCH_ASSOC);

            $controller = $this;
            require_once __DIR__ . '/../views/admin/settings.php';
        } catch (PDOException $e) {
            error_log("Settings error: " . $e->getMessage());
            $_SESSION['error'] = "Server error";
            header('Location: /admin/dashboard');
            exit;
        }
    }


    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }

        try {
            $user_id = $_SESSION['user_id'];
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validate inputs
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $_SESSION['error'] = "All password fields are required";
                header('Location: /admin/settings');
                exit;
            }
            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "New passwords do not match";
                header('Location: /admin/settings');
                exit;
            }
            if (strlen($new_password) < 8) {
                $_SESSION['error'] = "New password must be at least 8 characters long";
                header('Location: /admin/settings');
                exit;
            }

            // Verify current password
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                $_SESSION['error'] = "Current password is incorrect";
                header('Location: /admin/settings');
                exit;
            }

            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id");
            $stmt->execute([
                ':password_hash' => $new_password_hash,
                ':user_id' => $user_id
            ]);

            $_SESSION['success'] = "Password updated successfully";
            header('Location: /admin/settings');
            exit;
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update password";
            header('Location: /admin/settings');
            exit;
        }
    }
}
