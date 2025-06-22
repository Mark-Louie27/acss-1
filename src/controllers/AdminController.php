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

    public function users()
    {
        try {
            // Fetch users with roles, colleges, departments
            $stmt = $this->db->query("
                SELECT u.user_id, u.username, u.first_name, u.last_name, r.role_name, c.college_name, d.department_name
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                LEFT JOIN departments d ON u.department_id = d.department_id
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch roles, colleges, departments for form
            $roles = $this->db->query("SELECT role_id, role_name FROM roles")->fetchAll(PDO::FETCH_ASSOC);
            $colleges = $this->db->query("SELECT college_id, college_name FROM colleges")->fetchAll(PDO::FETCH_ASSOC);
            $departments = $this->db->query("SELECT department_id, department_name FROM departments")->fetchAll(PDO::FETCH_ASSOC);

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
}
