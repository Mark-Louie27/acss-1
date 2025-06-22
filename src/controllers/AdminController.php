<?php

require_once __DIR__ . '/../config/Database.php';

class AdminController
{
    public $db;

    public function __construct()  // Remove the $db parameter since we're not using it
    {
        error_log("AdminController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in AdminController");
            die("Database connection failed. Please try again later.");
        }
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

    public function users()
    {
        try {
            // Fetch users with roles, colleges, departments, and status
            $stmt = $this->db->query("
            SELECT u.user_id, u.username, u.first_name, u.last_name, u.is_active,
                   r.role_name, c.college_name, d.department_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN departments d ON u.department_id = d.department_id
            ORDER BY u.first_name, u.last_name
        ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch roles, colleges, departments for form
            $roles = $this->db->query("SELECT role_id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
            $colleges = $this->db->query("SELECT college_id, college_name FROM colleges ORDER BY college_name")->fetchAll(PDO::FETCH_ASSOC);
            $departments = $this->db->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

            $controller = $this;
            require_once __DIR__ . '/../views/admin/users.php';
        } catch (PDOException $e) {
            error_log("Users error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    public function createUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = $_POST['username'] ?? '';
                $password = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $role_id = $_POST['role_id'] ?? null;
                $college_id = $_POST['college_id'] ?: null;
                $department_id = $_POST['department_id'] ?: null;

                $stmt = $this->db->prepare("
                    INSERT INTO users (username, password, first_name, last_name, role_id, college_id, department_id)
                    VALUES (:username, :password, :first_name, :last_name, :role_id, :college_id, :department_id)
                ");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $password,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':role_id' => $role_id,
                    ':college_id' => $college_id,
                    ':department_id' => $department_id
                ]);

                header('Location: /admin/users');
                exit;
            } catch (PDOException $e) {
                error_log("Create user error: " . $e->getMessage());
                http_response_code(500);
                echo "Failed to create user";
            }
        }
    }

    /**
     * Disable a user account
     */
    public function disableUser($userId)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user exists
            $checkStmt = $this->db->prepare("SELECT user_id, username FROM users WHERE user_id = :user_id");
            $checkStmt->execute([':user_id' => $userId]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Don't allow disabling your own account (optional security check)
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                echo json_encode(['success' => false, 'message' => 'Cannot disable your own account']);
                return;
            }

            // Update user status
            $stmt = $this->db->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE user_id = :user_id");
            $result = $stmt->execute([':user_id' => $userId]);

            if ($result) {
                // Log the action (optional)
                error_log("User disabled: ID {$userId}, Username: {$user['username']} by admin ID: " . ($_SESSION['user_id'] ?? 'unknown'));

                echo json_encode(['success' => true, 'message' => 'User disabled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to disable user']);
            }
        } catch (PDOException $e) {
            error_log("Disable user error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    /**
     * Enable a user account
     */
    public function enableUser($userId)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user exists
            $checkStmt = $this->db->prepare("SELECT user_id, username FROM users WHERE user_id = :user_id");
            $checkStmt->execute([':user_id' => $userId]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Update user status
            $stmt = $this->db->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE user_id = :user_id");
            $result = $stmt->execute([':user_id' => $userId]);

            if ($result) {
                // Log the action (optional)
                error_log("User enabled: ID {$userId}, Username: {$user['username']} by admin ID: " . ($_SESSION['user_id'] ?? 'unknown'));

                echo json_encode(['success' => true, 'message' => 'User enabled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to enable user']);
            }
        } catch (PDOException $e) {
            error_log("Enable user error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    /**
     * View user details
     */
    public function viewUser($userId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT u.*, r.role_name, c.college_name, d.department_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN departments d ON u.department_id = d.department_id
            WHERE u.user_id = :user_id
        ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo "User not found";
                return;
            }

            require_once __DIR__ . '/../views/admin/view_user.php';
        } catch (PDOException $e) {
            error_log("View user error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    /**
     * Edit user form
     */
    public function editUser($userId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT u.*, r.role_name, c.college_name, d.department_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN departments d ON u.department_id = d.department_id
            WHERE u.user_id = :user_id
        ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo "User not found";
                return;
            }

            // Fetch roles, colleges, departments for form
            $roles = $this->db->query("SELECT role_id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
            $colleges = $this->db->query("SELECT college_id, college_name FROM colleges ORDER BY college_name")->fetchAll(PDO::FETCH_ASSOC);
            $departments = $this->db->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/admin/edit_user.php';
        } catch (PDOException $e) {
            error_log("Edit user error: " . $e->getMessage());
            http_response_code(500);
            echo "Server error";
        }
    }

    /**
     * Update user
     */
    public function updateUser($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = $_POST['username'] ?? '';
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $role_id = $_POST['role_id'] ?? null;
                $college_id = $_POST['college_id'] ?: null;
                $department_id = $_POST['department_id'] ?: null;

                // Build the update query
                $updateFields = [
                    'username = :username',
                    'first_name = :first_name',
                    'last_name = :last_name',
                    'role_id = :role_id',
                    'college_id = :college_id',
                    'department_id = :department_id',
                    'updated_at = NOW()'
                ];

                $params = [
                    ':user_id' => $userId,
                    ':username' => $username,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':role_id' => $role_id,
                    ':college_id' => $college_id,
                    ':department_id' => $department_id
                ];

                // Update password if provided
                if (!empty($_POST['password'])) {
                    $updateFields[] = 'password = :password';
                    $params[':password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
                }

                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                header('Location: /admin/users');
                exit;
            } catch (PDOException $e) {
                error_log("Update user error: " . $e->getMessage());
                http_response_code(500);
                echo "Failed to update user";
            }
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
                SELECT f.faculty_id, u.first_name, u.last_name, c.college_name
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                JOIN colleges c ON f.college_id = c.college_id
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

    public function profile()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.user_id, u.username, u.email, u.phone, u.first_name, u.middle_name, u.last_name, u.suffix, 
                       u.profile_picture, r.role_name, c.college_name, d.department_name
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.user_id = :user_id
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['error'] = "User not found";
                header('Location: /admin/dashboard');
                exit;
            }

            $controller = $this;
            require_once __DIR__ . '/../views/admin/profile.php';
        } catch (PDOException $e) {
            error_log("Profile error: " . $e->getMessage());
            $_SESSION['error'] = "Server error";
            header('Location: /admin/dashboard');
            exit;
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

    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }

        try {
            $user_id = $_SESSION['user_id'];
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '') ?: null;
            $last_name = trim($_POST['last_name'] ?? '');
            $suffix = trim($_POST['suffix'] ?? '') ?: null;
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '') ?: null;
            $college_id = $_POST['college_id'] ?: null;
            $department_id = $_POST['department_id'] ?: null;

            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($email)) {
                $_SESSION['error'] = "First name, last name, and email are required";
                header('Location: /admin/settings');
                exit;
            }

            // Handle profile picture upload
            $profile_picture = null;
            if (!empty($_FILES['profile_picture']['name'])) {
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_name = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
                $target_file = $upload_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Validate file
                $check = getimagesize($_FILES['profile_picture']['tmp_name']);
                if (
                    $check === false || $_FILES['profile_picture']['size'] > 5000000 ||
                    !in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])
                ) {
                    $_SESSION['error'] = "Invalid image file";
                    header('Location: /admin/settings');
                    exit;
                }

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    $profile_picture = 'uploads/profiles/' . $file_name;
                } else {
                    $_SESSION['error'] = "Failed to upload image";
                    header('Location: /admin/settings');
                    exit;
                }
            }

            $stmt = $this->db->prepare("
                UPDATE users
                SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name, 
                    suffix = :suffix, email = :email, phone = :phone, college_id = :college_id, 
                    department_id = :department_id" . ($profile_picture ? ", profile_picture = :profile_picture" : "") . "
                WHERE user_id = :user_id
            ");
            $params = [
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':suffix' => $suffix,
                ':email' => $email,
                ':phone' => $phone,
                ':college_id' => $college_id,
                ':department_id' => $department_id,
                ':user_id' => $user_id
            ];
            if ($profile_picture) {
                $params[':profile_picture'] = $profile_picture;
            }
            $stmt->execute($params);

            $_SESSION['success'] = "Profile updated successfully";
            header('Location: /admin/profile');
            exit;
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update profile";
            header('Location: /admin/settings');
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
