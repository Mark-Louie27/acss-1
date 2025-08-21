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
            $this->redirectBasedOnRole();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $employeeId = trim($_POST['employee_id'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($employeeId) || empty($password)) {
                error_log("Login failed: Missing employee_id or password");
                $error = "Employee ID and password are required.";
                require_once __DIR__ . '/../views/auth/login.php';
                return;
            }

            // Check if user exists and get is_active status
            $query = "
                SELECT u.user_id, u.password_hash, u.is_active, u.role_id
                FROM users u
                WHERE u.employee_id = :employee_id
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':employee_id' => $employeeId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['is_active'] == 0) {
                    error_log("Login failed for employee_id: $employeeId - Account is pending approval");
                    $error = "Your account is pending approval. Please contact the Dean.";
                    require_once __DIR__ . '/../views/auth/login.php';
                    return;
                }

                $userData = $this->authService->login($employeeId, $password);
                if ($userData) {
                    $this->authService->startSession($userData);
                    error_log("Login successful for employee_id: $employeeId");
                    $this->redirectBasedOnRole();
                } else {
                    error_log("Login failed for employee_id: $employeeId - Unexpected error");
                    $error = "An unexpected error occurred. Please try again.";
                    require_once __DIR__ . '/../views/auth/login.php';
                }
            } else {
                error_log("Login failed for employee_id: $employeeId - Invalid credentials");
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
            $this->redirectBasedOnRole();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'employee_id' => trim($_POST['employee_id'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'email' => trim($_POST['email'] ?? ''),
                'first_name' => trim($_POST['first_name'] ?? ''),
                'middle_name' => trim($_POST['middle_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'suffix' => trim($_POST['suffix'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role_id' => intval($_POST['role_id'] ?? 0),
                'college_id' => intval($_POST['college_id'] ?? 0),
                'department_id' => intval($_POST['department_id'] ?? 0),
                'academic_rank' => $_POST['academic_rank'] ?? 'Instructor',
                'employment_type' => $_POST['employment_type'] ?? 'Part-time',
                'program_id' => !empty($_POST['program_id']) ? intval($_POST['program_id']) : null
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
            if ($data['role_id'] == 5 && empty($data['program_id'])) {
                $errors[] = "Program ID is required for Program Chair.";
            }

            if (empty($errors)) {
                try {
                    if ($this->authService->register($data)) {
                        $success = $data['role_id'] == 5 || $data['role_id'] == 6
                            ? "Registration submitted successfully. Awaiting Dean approval."
                            : "Registration successful. You can now log in.";
                        header('Location: /login?success=' . urlencode($success));
                        exit;
                    } else {
                        $error = "Registration failed. Employee ID or email may already be in use.";
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
            $collegeId = intval($_GET['college_id'] ?? 0);
            if ($collegeId < 1) {
                throw new Exception("Invalid college ID");
            }
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
     */
    private function redirectBasedOnRole()
    {
        if (!isset($_SESSION['role_id'])) {
            $this->logout();
            exit;
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
                header('Location: /director/dashboard');
                break;
            case 4: // Dean
                header('Location: /dean/dashboard');
                break;
            case 5: // Program Chair
                header('Location: /chair/dashboard');
                break;
            case 6: // Faculty
                header('Location: /faculty/dashboard');
                break;
            default:
                $this->logout();
                exit;
        }
        exit;
    }
}
