<?php
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../config/Database.php';

class AuthController
{
    private $authService;
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->authService = new AuthService($this->db);
    }

    /**
     * Handle login request
     */
    public function login()
    {
        // If already logged in, redirect to appropriate dashboard
        if ($this->authService->isLoggedIn()) {
            $this->redirectBasedOnRole($_SESSION['role_id']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $employeeId = $_POST['employee_id'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->authService->login($employeeId, $password);
            if ($user) {
                $this->authService->startSession($user);
                error_log("Login successful for employee_id: $employeeId");
                $this->redirectBasedOnRole($user['role_id']);
            } else {
                error_log("Login failed for employee_id: $employeeId");
                $error = "Invalid Employee ID or password.";
                require_once __DIR__ . '/../views/auth/login.php';
            }
        } else {
            require_once __DIR__ . '/../views/auth/login.php';
        }
    }

    /**
     * Handle registration request
     */
    public function register()
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirectBasedOnRole($_SESSION['role_id']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'employee_id' => trim($_POST['employee_id'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'first_name' => trim($_POST['first_name'] ?? ''),
                'middle_name' => trim($_POST['middle_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'suffix' => trim($_POST['suffix'] ?? ''),
                'role_id' => intval($_POST['role_id']),
                'college_id' => intval($_POST['college_id']),
                'department_id' => intval($_POST['department_id']),
                'classification' => $_POST['classification'] ?? null,
                'academic_rank' => $_POST['academic_rank'] ?? 'Instructor',
                'employment_type' => $_POST['employment_type'] ?? 'Regular'
            ];

            $errors = [];
            if (empty($data['employee_id'])) $errors[] = "Employee ID is required.";
            if (empty($data['username'])) $errors[] = "Username is required.";
            if (empty($data['password']) || strlen($data['password']) < 6) $errors[] = "Password must be at least 6 characters.";
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
            if (empty($data['first_name'])) $errors[] = "First name is required.";
            if (empty($data['last_name'])) $errors[] = "Last name is required.";
            if ($data['role_id'] < 1 || $data['role_id'] > 6) $errors[] = "Invalid role selected.";
            if ($data['college_id'] < 1) $errors[] = "Invalid college selected.";
            if ($data['department_id'] < 1) $errors[] = "Invalid department selected.";
            if ($data['role_id'] == 6 && (empty($data['academic_rank']) || empty($data['employment_type']))) {
                $errors[] = "Academic rank and employment type are required for Faculty.";
            }

            if (empty($errors)) {
                try {
                    if ($this->authService->register($data)) {
                        header('Location: /login?success=Registration successful. Please login.');
                        exit;
                    } else {
                        $error = "Registration failed. Employee ID or username may already be in use.";
                        require_once __DIR__ . '/../views/auth/register.php';
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    require_once __DIR__ . '/../views/auth/register.php';
                }
            } else {
                $error = implode("<br>", $errors);
                require_once __DIR__ . '/../views/auth/register.php';
            }
        } else {
            require_once __DIR__ . '/../views/auth/register.php';
        }
    }

    /**
     * Get departments API endpoint
     */
    public function getDepartments()
    {
        header('Content-Type: application/json');

        try {
            $collegeId = $_GET['college_id'] ?? 0;
            $userModel = new UserModel();
            $departments = $userModel->getDepartmentsByCollege($collegeId);

            echo json_encode([
                'success' => true,
                'departments' => $departments
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle logout request
     */
    public function logout()
    {
        $this->authService->logout();
        header('Location: /home');
        exit;
    }

    /**
     * Redirect based on user role
     * @param int $roleId
     */
    private function redirectBasedOnRole()
    {
        if (!isset($_SESSION['role_id'])) {
            $this->logout();
            exit();
        }

        $roleId = (int)$_SESSION['role_id'];

        switch ($roleId) {
            case 1: // Admin
                header('Location: /admin/dashboard');
                break;
            case 2: // VPAA
                header('Location: /vp/dashboard');
                break;
            case 3: // DI
                header('Location: /di/dashboard');
                break;
            case 4: // Dean
                header('Location: /dean/dashboard');
                break;
            case 5: // chair
                header('Location: /chair/dashboard');
                break;
            case 6: // Faculty
                header('Location: /faculty/dashboard');
                break;
            default:
                $this->logout();
                exit();
        }
        exit();
    }
}
