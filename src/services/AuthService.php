<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthService
{
    private $userModel;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new UserModel($db);
    }

    /**
     * Authenticate a user
     * @param string $employeeId
     * @param string $password
     * @return array|bool
     */
    public function login($employeeId, $password)
    {
        try {
            $query = "
            SELECT u.user_id, u.employee_id, u.username, u.first_name, u.last_name, 
                   u.password_hash, u.role_id, u.profile_picture, u.is_active
            FROM users u
            WHERE u.employee_id = :employee_id
        ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
                $this->logAuthAction($user['user_id'], 'login_success', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                return [
                    'user_id' => $user['user_id'],
                    'employee_id' => $user['employee_id'],
                    'username' => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role_id' => $user['role_id'],
                    'profile_picture' => $user['profile_picture'] ?? null, // Default profile picture
                ];
            } else {
                $this->logAuthAction(null, 'login_failed', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $employeeId);
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error during login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a new user
     * @param array $data
     * @return bool
     */
    public function register($data)
    {
        try {
            // Check if email already exists
            if ($this->userModel->emailExists($data['email'])) {
                throw new Exception("Email already exists. Please use a different email.");
            }

            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $data['is_active'] = 1; // Default to active

            $this->db->beginTransaction();
            $userId = $this->userModel->createUser($data);

            if ($userId) {
                // Fetch a program_id if needed (for Program Chair and Faculty roles)
                $program_id = null;
                if (in_array($data['role_id'], [5, 6])) { // Program Chair or Faculty
                    $programs = $this->userModel->getProgramsByDepartment($data['department_id']);
                    if (!empty($programs)) {
                        $program_id = $programs[0]['program_id']; // Take the first program as default
                    } else {
                        throw new Exception("No programs found for the selected department.");
                    }
                }

                // Handle role-specific data
                switch ($data['role_id']) {
                    case 1: // Admin
                        // Assuming no specific table for Admin, just log the success
                        break;
                    case 2: // VPAA
                        // Assuming no specific table for VPAA, just log the success
                        break;
                    case 3: // Department Instructor (D.I)
                        $success = $this->userModel->createDepartmentInstructor([
                            'user_id' => $userId,
                            'department_id' => $data['department_id'],
                            'start_date' => date('Y-m-d')
                        ]);
                        if (!$success) {
                            throw new Exception("Failed to create Department Instructor record.");
                        }
                        break;
                    case 4: // Dean
                        $success = $this->userModel->createDean([
                            'user_id' => $userId,
                            'college_id' => $data['college_id'],
                            'start_date' => date('Y-m-d')
                        ]);
                        if (!$success) {
                            throw new Exception("Failed to create Dean record.");
                        }
                        break;
                    case 5: // Program Chair
                        if (!$program_id) {
                            throw new Exception("Program ID is required for Program Chair role.");
                        }
                        $success = $this->userModel->createProgramChair([
                            'user_id' => $userId,
                            'program_id' => $program_id,
                            'start_date' => date('Y-m-d')
                        ]);
                        if (!$success) {
                            throw new Exception("Failed to create Program Chair record.");
                        }
                        break;
                    case 6: // Faculty
                        if (!$program_id) {
                            throw new Exception("Program ID is required for Faculty role.");
                        }
                        $success = $this->userModel->createFaculty([
                            'user_id' => $userId,
                            'employee_id' => $data['employee_id'],
                            'academic_rank' => $data['academic_rank'] ?? 'Instructor',
                            'employment_type' => $data['employment_type'] ?? 'Regular',
                            'classification' => $data['classification'] ?? 'TL',
                            'primary_program_id' => $program_id
                        ]);
                        if (!$success) {
                            throw new Exception("Failed to create Faculty record.");
                        }
                        break;
                }

                $this->logAuthAction($userId, 'register_success', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                $this->db->commit();
                return true;
            } else {
                throw new Exception("Failed to create user.");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error during registration: " . $e->getMessage());
            throw $e; // Re-throw to let the controller handle the error
        }
    }

    public function verifyCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Log authentication actions
     * @param int|null $userId
     * @param string $action
     * @param string $ipAddress
     * @param string $userAgent
     * @param string|null $identifier
     * @return void
     */
    private function logAuthAction($userId, $action, $ipAddress, $userAgent, $identifier = null)
    {
        try {
            $query = "
                INSERT INTO auth_logs (user_id, action, ip_address, user_agent, identifier, created_at)
                VALUES (:user_id, :action, :ip_address, :user_agent, :identifier, NOW())
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
            $stmt->bindParam(':user_agent', $userAgent, PDO::PARAM_STR);
            $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error logging auth action: " . $e->getMessage());
        }
    }

    /**
     * Start a session for a user
     * @param array $user
     * @return void
     */
    public function startSession($user)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['user_id'];  // Integer
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role_id'] = $user['role_id'];  // Integer
        $_SESSION['profile_picture'] = $user['profile_picture'] ?? null; // Default profile picture
        $_SESSION['logged_in'] = true;

        session_regenerate_id(true);  // Prevent session fixation
    }

    /**
     * Destroy the current session
     * @return void
     */
    public function logout()
    {
        // session_start() is handled in index.php
        session_unset();
        session_destroy();
    }

    /**
     * Check if a user is logged in
     * @return bool
     */
    public function isLoggedIn()
    {
        // session_start() is handled in index.php
        return isset($_SESSION['user_id']);
    }
}
