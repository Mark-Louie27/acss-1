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
                   u.password_hash, u.role_id, u.is_active
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
                    'role_id' => $user['role_id']
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
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $data['is_active'] = 1; // Default to active

            $this->db->beginTransaction();
            $userId = $this->userModel->createUser($data);

            if ($userId) {
                // Handle role-specific data
                switch ($data['role_id']) {
                    case 3: // Department Instructor (D.I)
                        $this->userModel->createDepartmentInstructor([
                            'user_id' => $userId,
                            'department_id' => $data['department_id'],
                            'start_date' => date('Y-m-d')
                        ]);
                        break;
                    case 4: // Dean
                        $this->userModel->createDean([
                            'user_id' => $userId,
                            'college_id' => $data['college_id'],
                            'start_date' => date('Y-m-d')
                        ]);
                        break;
                    case 5: // Chair
                        $this->userModel->createProgramChair([
                            'user_id' => $userId,
                            'program_id' => $data['program_id'] ?? 1, // Default to BSIT
                            'start_date' => date('Y-m-d')
                        ]);
                        break;
                    case 6: // Faculty
                        $this->userModel->createFaculty([
                            'user_id' => $userId,
                            'employee_id' => $data['employee_id'],
                            'academic_rank' => $data['academic_rank'] ?? 'Instructor',
                            'employment_type' => $data['employment_type'] ?? 'Regular',
                            'department_id' => $data['department_id'],
                            'primary_program_id' => $data['program_id'] ?? null
                        ]);
                        break;
                }

                $this->logAuthAction($userId, 'register_success', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error during registration: " . $e->getMessage());
            return false;
        }
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
