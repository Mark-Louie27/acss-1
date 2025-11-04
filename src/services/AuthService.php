<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../services/EmailService.php';

class AuthService
{
    private $userModel;
    private $db;
    private $emailService;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new UserModel();
        $this->emailService = new EmailService();
    }

    /**
     * Login a user
     * @param string $employeeId
     * @param string $password
     * @return array|bool User data on success, false on failure
     */
    public function login($employeeId, $password)
    {
        try {
            $query = "
            SELECT u.user_id, u.employee_id, u.title, u.username, u.first_name, u.last_name, 
                   u.password_hash, u.profile_picture, u.is_active,
                   u.department_id, u.college_id, u.email, u.middle_name, u.suffix
            FROM users u
            WHERE u.employee_id = :employee_id
        ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
                // Fetch ALL roles for this user
                $roleStmt = $this->db->prepare("
                SELECT r.role_name 
                FROM roles r 
                JOIN user_roles ur ON r.role_id = ur.role_id 
                WHERE ur.user_id = :user_id
            ");
                $roleStmt->execute([':user_id' => $user['user_id']]);
                $roles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

                $this->logAuthAction($user['user_id'], 'login_success', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);

                return [
                    'user_id' => $user['user_id'],
                    'employee_id' => $user['employee_id'],
                    'title' => $user['title'],
                    'username' => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'middle_name' => $user['middle_name'] ?? '',
                    'suffix' => $user['suffix'] ?? '',
                    'email' => $user['email'],
                  
                    'roles' => $roles,  // CHANGED: Array of all roles, not single role_name
                    'department_id' => $user['department_id'],
                    'college_id' => $user['college_id'],
                    'profile_picture' => $user['profile_picture'] ?? null,
                    'is_active' => $user['is_active'],
                ];
            } else {
                $this->logAuthAction(null, 'login_failed', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $employeeId);
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error during login for employee_id $employeeId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a new user
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function checkExistingRoles($collegeId, $departmentId, $programId)
    {
        $existingRoles = [];
        $query = "
        SELECT ur.role_id, r.role_name, u.college_id, u.department_id, pc.program_id
        FROM user_roles ur
        JOIN users u ON ur.user_id = u.user_id
        JOIN roles r ON ur.role_id = r.role_id
        LEFT JOIN program_chairs pc ON u.user_id = pc.user_id AND pc.is_current = 1
        WHERE u.is_active = 1 AND (ur.role_id = 1 OR ur.role_id = 4 OR ur.role_id = 5)
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($roles as $role) {
            if ($role['role_id'] == 1) { // Admin (assume one per system, ignore college/dept for now)
                $existingRoles[1] = ['role_name' => $role['role_name'], 'college_id' => null, 'department_id' => null, 'program_id' => null];
            }
            if ($role['role_id'] == 4 && $role['college_id'] == $collegeId) { // Dean
                $existingRoles[4] = ['role_name' => $role['role_name'], 'college_id' => $role['college_id'], 'department_id' => null, 'program_id' => null];
            }
            if ($role['role_id'] == 5 && $role['program_id'] == $programId) { // Program Chair
                $existingRoles[5] = ['role_name' => $role['role_name'], 'college_id' => null, 'department_id' => null, 'program_id' => $role['program_id']];
            }
        }
        return $existingRoles;
    }

    public function register($data)
    {
        try {
            // Start transaction with logging
            error_log("Starting transaction for employee_id: {$data['employee_id']}");
            if (!$this->db->beginTransaction()) {
                throw new Exception("Failed to start transaction");
            }
            error_log("Transaction started for employee_id: {$data['employee_id']}");

            // Validate required fields
            $required_fields = ['employee_id', 'username', 'password', 'email', 'first_name', 'last_name', 'department_id', 'college_id', 'role_id'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Check for duplicates
            if ($this->userModel->employeeIdExists($data['employee_id'])) {
                throw new Exception("Employee ID {$data['employee_id']} already exists");
            }
            if ($this->userModel->emailExists($data['email'])) {
                throw new Exception("Email {$data['email']} already exists");
            }

            // Validate role uniqueness before registration
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $roleId) {
                    // Check college-level roles (Admin=1, Dean=4)
                    if (in_array($roleId, [1, 4])) {
                        $existingUser = $this->checkExistingCollegeRole($roleId, $data['college_id']);
                        if ($existingUser) {
                            $roleName = $roleId == 1 ? 'Admin' : 'Dean';
                            throw new Exception("A $roleName already exists for this college: {$existingUser['first_name']} {$existingUser['last_name']} (Employee ID: {$existingUser['employee_id']})");
                        }
                    }

                    // Check department-level roles for Program Chair (role_id=5)
                    if ($roleId == 5) {
                        $departmentIds = $data['department_ids'] ?? [$data['department_id']];

                        foreach ($departmentIds as $deptId) {
                            $existingUser = $this->checkExistingDepartmentRole($roleId, $deptId);
                            if ($existingUser) {
                                $deptInfo = $this->getDepartmentInfo($deptId);
                                throw new Exception("A Program Chair already exists for {$deptInfo['department_name']}: {$existingUser['first_name']} {$existingUser['last_name']} (Employee ID: {$existingUser['employee_id']})");
                            }
                        }
                    }
                }
            }

            // Insert into users table (all users pending approval)
            $query = "
            INSERT INTO users (
                employee_id, username, password_hash, email, first_name, middle_name,
                last_name, suffix, department_id, college_id, role_id, is_active, created_at
            ) VALUES (
                :employee_id, :username, :password_hash, :email, :first_name, :middle_name,
                :last_name, :suffix, :department_id, :college_id, :role_id, 0, NOW()
            )";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':employee_id' => $data['employee_id'],
                ':username' => $data['username'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':email' => $data['email'],
                ':first_name' => $data['first_name'],
                ':middle_name' => $data['middle_name'] ?? null,
                ':last_name' => $data['last_name'],
                ':suffix' => $data['suffix'] ?? null,
                ':department_id' => $data['department_id'], // Primary department
                ':college_id' => $data['college_id'],
                ':role_id' => $data['role_id']
            ]);
            $userId = $this->db->lastInsertId();
            error_log("register: Inserted user_id=$userId, employee_id={$data['employee_id']}, role_id={$data['role_id']}, is_active=0");

            // Insert into faculty table for all users
            $query = "
            INSERT INTO faculty (user_id, employee_id, academic_rank, employment_type, classification, max_hours)
            VALUES (:user_id, :employee_id, :academic_rank, :employment_type, :classification, :max_hours)
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':employee_id' => $data['employee_id'],
                ':academic_rank' => $data['academic_rank'] ?? 'Instructor',
                ':employment_type' => $data['employment_type'] ?? 'Part-time',
                ':classification' => $data['classification'] ?? null,
                ':max_hours' => $data['max_hours'] ?? 18.00
            ]);
            $facultyId = $this->db->lastInsertId();
            error_log("register: Inserted faculty_id=$facultyId for user_id=$userId");

            // Insert roles into user_roles
            if (!empty($data['roles'])) {
                $roleQuery = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $roleStmt = $this->db->prepare($roleQuery);
                foreach ($data['roles'] as $roleId) {
                    $roleStmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
                    error_log("register: Assigned role_id=$roleId to user_id=$userId");
                }
            }

            // Role-specific assignments
            foreach ($data['roles'] as $roleId) {
                if ($roleId == 3) { // Director
                    $startDate = $data['start_date'] ?? date('Y-m-d');
                    $query = "
                    INSERT INTO department_instructors (user_id, department_id, start_date, is_current)
                    VALUES (:user_id, :department_id, :start_date, 1)
                ";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':department_id' => $data['department_id'],
                        ':start_date' => $startDate
                    ]);
                    error_log("register: Inserted department_instructor for user_id=$userId");
                }

                if ($roleId == 4) { // Dean
                    $startDate = $data['start_date'] ?? date('Y-m-d');
                    $query = "
                    INSERT INTO deans (user_id, college_id, start_date, is_current)
                    VALUES (:user_id, :college_id, :start_date, 1)
                ";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':college_id' => $data['college_id'],
                        ':start_date' => $startDate
                    ]);
                    error_log("register: Inserted dean for user_id=$userId");
                }

                if ($roleId == 5 && !empty($data['program_id'])) { // Program Chair
                    $query = "
                    INSERT INTO program_chairs (faculty_id, user_id, program_id, start_date, is_current)
                    VALUES (:faculty_id, :user_id, :program_id, :start_date, 1)
                ";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':faculty_id' => $facultyId,
                        ':user_id' => $userId,
                        ':program_id' => $data['program_id'],
                        ':start_date' => date('Y-m-d')
                    ]);
                    error_log("register: Inserted program_chair for user_id=$userId, program_id={$data['program_id']}");

                    // Insert into chair_departments for multiple department support
                    if (isset($data['department_ids']) && is_array($data['department_ids']) && count($data['department_ids']) > 0) {
                        $primaryDeptId = $data['department_id']; // Primary department from main field
                        $deptStmt = $this->db->prepare("
                        INSERT INTO chair_departments (user_id, department_id, is_primary, assigned_date)
                        VALUES (:user_id, :department_id, :is_primary, NOW())
                    ");

                        foreach ($data['department_ids'] as $deptId) {
                            $isPrimary = ($deptId == $primaryDeptId) ? 1 : 0;
                            $deptStmt->execute([
                                ':user_id' => $userId,
                                ':department_id' => $deptId,
                                ':is_primary' => $isPrimary
                            ]);

                            error_log("register: Assigned department $deptId to user $userId (Primary: " . ($isPrimary ? 'Yes' : 'No') . ")");
                        }

                        error_log("register: Assigned " . count($data['department_ids']) . " departments to Program Chair user_id=$userId");
                    } else {
                        // Single department - still add to chair_departments for consistency
                        $deptStmt = $this->db->prepare("
                        INSERT INTO chair_departments (user_id, department_id, is_primary, assigned_date)
                        VALUES (:user_id, :department_id, 1, NOW())
                    ");
                        $deptStmt->execute([
                            ':user_id' => $userId,
                            ':department_id' => $data['department_id']
                        ]);

                        error_log("register: Assigned primary department {$data['department_id']} to Program Chair user_id=$userId");
                    }
                }
            }

            $this->db->commit();
            error_log("Transaction committed for employee_id: {$data['employee_id']}");

            // Send confirmation email
            $fullName = "{$data['first_name']} {$data['last_name']}";
            $roleNames = array_column(array_filter($this->userModel->getRoles(), fn($r) => in_array($r['role_id'], $data['roles'])), 'role_name');
            $this->emailService->sendConfirmationEmail($data['email'], $fullName, implode(', ', $roleNames) ?: 'Unknown Role');

            $this->logAuthAction($userId, 'request_submitted', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $data['employee_id']);
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                error_log("Transaction rolled back for employee_id: {$data['employee_id']}");
            }
            error_log("Error during registration for employee_id {$data['employee_id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if a college-level role already exists
     */
    private function checkExistingCollegeRole($roleId, $collegeId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.user_id, u.employee_id, u.first_name, u.last_name, r.role_name
                FROM users u
                INNER JOIN user_roles ur ON u.user_id = ur.user_id
                INNER JOIN roles r ON ur.role_id = r.role_id
                WHERE u.college_id = :college_id
                AND ur.role_id = :role_id
                AND u.is_active = 1
                LIMIT 1
            ");

            $stmt->execute([
                ':college_id' => $collegeId,
                ':role_id' => $roleId
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error checking existing college role: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a department-level role already exists
     */
    private function checkExistingDepartmentRole($roleId, $departmentId)
    {
        try {
            // For Program Chair, check both users table and chair_departments
            if ($roleId == 5) { // Program Chair
                $stmt = $this->db->prepare("
                    SELECT DISTINCT u.user_id, u.employee_id, u.first_name, u.last_name, r.role_name
                    FROM users u
                    INNER JOIN user_roles ur ON u.user_id = ur.user_id
                    INNER JOIN roles r ON ur.role_id = r.role_id
                    LEFT JOIN chair_departments cd ON u.user_id = cd.user_id
                    WHERE ur.role_id = :role_id
                    AND (u.department_id = :department_id OR cd.department_id = :department_id)
                    AND u.is_active = 1
                    LIMIT 1
                ");
            } else {
                // For other department roles
                $stmt = $this->db->prepare("
                    SELECT u.user_id, u.employee_id, u.first_name, u.last_name, r.role_name
                    FROM users u
                    INNER JOIN user_roles ur ON u.user_id = ur.user_id
                    INNER JOIN roles r ON ur.role_id = r.role_id
                    WHERE ur.role_id = :role_id
                    AND u.department_id = :department_id
                    AND u.is_active = 1
                    LIMIT 1
                ");
            }

            $stmt->execute([
                ':role_id' => $roleId,
                ':department_id' => $departmentId
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error checking existing department role: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a Program Chair already exists for a department
     */
    private function checkExistingProgramChair($departmentId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT DISTINCT u.user_id, u.employee_id, u.first_name, u.last_name, r.role_name
            FROM users u
            INNER JOIN user_roles ur ON u.user_id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.role_id
            WHERE ur.role_id = 5
            AND u.is_active = 1
            AND (
                -- Check chair_departments table (multiple department assignments)
                u.user_id IN (
                    SELECT cd.user_id 
                    FROM chair_departments cd 
                    WHERE cd.department_id = :department_id
                )
                OR
                -- Check program_chairs table (through programs -> departments)
                u.user_id IN (
                    SELECT pc.user_id 
                    FROM program_chairs pc 
                    INNER JOIN programs p ON pc.program_id = p.program_id 
                    WHERE p.department_id = :department_id 
                    AND pc.is_current = 1
                )
                OR
                -- Check users table (direct department assignment)
                u.department_id = :department_id
            )
            LIMIT 1
        ");

            $stmt->execute([
                ':department_id' => $departmentId
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error checking existing Program Chair for department_id=$departmentId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get department information
     */
    private function getDepartmentInfo($departmentId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT d.department_name, d.department_code, c.college_name
            FROM departments d
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE d.department_id = :department_id
        ");

            $stmt->execute([':department_id' => $departmentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: ['department_name' => 'Unknown Department', 'department_code' => '', 'college_name' => ''];
        } catch (PDOException $e) {
            error_log("Error getting department info: " . $e->getMessage());
            return ['department_name' => 'Unknown Department', 'department_code' => '', 'college_name' => ''];
        }
    }

    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    public function verifyCsrfToken($token)
    {
        $expectedToken = $_SESSION['csrf_token'] ?? '';
        $isValid = !empty($token) && hash_equals($expectedToken, $token);
        error_log("verifyCsrfToken: token=$token, expected=$expectedToken, isValid=" . ($isValid ? 'true' : 'false'));
        return $isValid;
    }

    /**
     * Generate CSRF token
     * @return string
     */
    public function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function loginWithToken($token)
    {
        if (empty($token)) {
            error_log("loginWithToken: Empty token provided");
            return null;
        }

        try {
            // Query user with remember token
            $query = "SELECT u.user_id, u.employee_id, u.is_active, u.email, u.first_name, u.last_name, u.middle_name, u.title, u.suffix 
                  FROM users u 
                  WHERE u.remember_token = :token AND u.remember_token_expiry > NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                error_log("loginWithToken: No user found for token or token expired");
                return null;
            }

            if ($user['is_active'] == 0) {
                error_log("loginWithToken: User is inactive - user_id: " . $user['user_id']);
                return null;
            }

            // Fetch roles for the user
            $roleStmt = $this->db->prepare("
            SELECT r.role_name 
            FROM roles r 
            JOIN user_roles ur ON r.role_id = ur.role_id 
            WHERE ur.user_id = :user_id
        ");
            $roleStmt->execute([':user_id' => $user['user_id']]);
            $roles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

            $user['roles'] = $roles;

            error_log("loginWithToken: User authenticated - user_id: " . $user['user_id'] . ", roles: " . json_encode($roles));

            return $user;
        } catch (PDOException $e) {
            error_log("loginWithToken: Database error - " . $e->getMessage());
            return null;
        }
    }

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
            error_log("Error logging auth action for user_id " . ($userId ?? 'null') . ": " . $e->getMessage());
        }
    }

    public function submitAdmission($data)
    {
        try {
            // Start transaction
            if (!$this->db->beginTransaction()) {
                throw new Exception("Failed to start transaction");
            }

            // Check if Dean is selected
            $isDean = in_array(4, $data['roles'] ?? []);
            $isProgramChair = in_array(5, $data['roles'] ?? []);

            // Validate required fields - adjust based on role
            $required_fields = ['employee_id', 'username', 'password', 'email', 'first_name', 'last_name', 'college_id', 'role_id'];

            // Department is required for all roles except Dean
            if (!$isDean) {
                $required_fields[] = 'department_id';
            }

            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Check for duplicates in both admissions and users tables
            if ($this->checkAdmissionExists($data['employee_id'], $data['username'], $data['email'])) {
                throw new Exception("Employee ID, username, or email already exists in pending admissions or active users");
            }

            if ($this->userModel->employeeIdExists($data['employee_id'])) {
                throw new Exception("Employee ID {$data['employee_id']} already exists in active users");
            }

            if ($this->userModel->emailExists($data['email'])) {
                throw new Exception("Email {$data['email']} already exists in active users");
            }

            // Check for existing college-level roles (Dean)
            if ($isDean) {
                $existingDean = $this->checkExistingCollegeRole(4, $data['college_id']);
                if ($existingDean) {
                    throw new Exception("A Dean already exists for this college: {$existingDean['first_name']} {$existingDean['last_name']} (Employee ID: {$existingDean['employee_id']})");
                }
            }

            // Check for existing department-level roles (Program Chair)
            if ($isProgramChair && !empty($data['department_ids'])) {
                foreach ($data['department_ids'] as $deptId) {
                    error_log("Checking for existing Program Chair in department_id: " . $deptId);
                    $existingChair = $this->checkExistingProgramChair($deptId);
                    if ($existingChair) {
                        error_log("Found existing Program Chair: " . json_encode($existingChair));
                        throw new Exception("A Program Chair already exists for this department: {$existingChair['first_name']} {$existingChair['last_name']} (Employee ID: {$existingChair['employee_id']})");
                    } else {
                        error_log("No existing Program Chair found for department_id: " . $deptId);
                    }
                }
            }

            // For single department roles (not Program Chair), check if role already exists in department
            if (!$isDean && !$isProgramChair && !empty($data['department_id'])) {
                $existingRole = $this->checkExistingDepartmentRole($data['role_id'], $data['department_id']);
                if ($existingRole) {
                    $roleName = $this->getRoleNames($data['role_id']);
                    throw new Exception("A {$roleName} already exists for this department: {$existingRole['first_name']} {$existingRole['last_name']} (Employee ID: {$existingRole['employee_id']})");
                }
            }

            // Handle department_id for Dean - can be NULL
            $departmentId = $isDean ? ($data['department_id'] ?? null) : $data['department_id'];

            // Insert into admissions table
            $query = "
                INSERT INTO admissions (
                    employee_id, username, password_hash, email, phone, title, first_name, middle_name,
                    last_name, suffix, role_id, department_id, college_id, academic_rank, 
                    employment_type, classification, status, submitted_at
                ) VALUES (
                    :employee_id, :username, :password_hash, :email, :phone, :title, :first_name, :middle_name,
                    :last_name, :suffix, :role_id, :department_id, :college_id, :academic_rank,
                    :employment_type, :classification, 'pending', NOW()
                )";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':employee_id' => $data['employee_id'],
                ':username' => $data['username'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':title' => $data['title'] ?? null,
                ':first_name' => $data['first_name'],
                ':middle_name' => $data['middle_name'] ?? null,
                ':last_name' => $data['last_name'],
                ':suffix' => $data['suffix'] ?? null,
                ':role_id' => $data['role_id'],
                ':department_id' => $departmentId,
                ':college_id' => $data['college_id'],
                ':academic_rank' => $data['academic_rank'] ?? null,
                ':employment_type' => $data['employment_type'] ?? null,
                ':classification' => $data['classification'] ?? null
            ]);

            $admissionId = $this->db->lastInsertId();

            // Insert roles into admission_roles
            if (!empty($data['roles'])) {
                $roleQuery = "INSERT INTO admission_roles (admission_id, role_id) VALUES (:admission_id, :role_id)";
                $roleStmt = $this->db->prepare($roleQuery);
                foreach ($data['roles'] as $roleId) {
                    $roleStmt->execute([':admission_id' => $admissionId, ':role_id' => $roleId]);
                    error_log("submitAdmission: Assigned role_id=$roleId to admission_id=$admissionId");
                }
            }

            // For Program Chair, store multiple departments
            if ($isProgramChair && !empty($data['department_ids'])) {
                $deptQuery = "INSERT INTO admission_departments (admission_id, department_id, is_primary) VALUES (:admission_id, :department_id, :is_primary)";
                $deptStmt = $this->db->prepare($deptQuery);

                foreach ($data['department_ids'] as $deptId) {
                    $isPrimary = ($deptId == $data['department_id']) ? 1 : 0;
                    $deptStmt->execute([
                        ':admission_id' => $admissionId,
                        ':department_id' => $deptId,
                        ':is_primary' => $isPrimary
                    ]);
                }
            }

            $this->db->commit();

            // Send notification email to admin
            $this->sendAdmissionNotification($data);

            $this->logAuthAction(null, 'admission_submitted', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $data['employee_id']);
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error during admission submission for employee_id {$data['employee_id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if admission already exists
     */
    private function checkAdmissionExists($employeeId, $username, $email)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM admissions 
                WHERE (employee_id = :employee_id OR username = :username OR email = :email) 
                AND status = 'pending'
            ");
            $stmt->execute([
                ':employee_id' => $employeeId,
                ':username' => $username,
                ':email' => $email
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking admission existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification email to admin about new admission
     */
    private function sendAdmissionNotification($data)
    {
        try {
            $emailService = new EmailService();
            $adminEmails = $this->getAdminEmails();

            $subject = "New User Admission Request - " . $data['employee_id'];
            $message = "
            A new user has submitted an admission request:\n\n
            Name: {$data['first_name']} {$data['last_name']}\n
            Employee ID: {$data['employee_id']}\n
            Email: {$data['email']}\n
            College: " . $this->getCollegeName($data['college_id']) . "\n
            Department: " . $this->getDepartmentName($data['department_id']) . "\n
            Roles: " . implode(', ', $this->getRoleNames($data['roles'])) . "\n\n
            Please review and approve/reject this admission request in the admin panel.
        ";

            foreach ($adminEmails as $adminEmail) {
                $emailService->sendEmail($adminEmail, $subject, $message);
            }
        } catch (Exception $e) {
            error_log("Error sending admission notification: " . $e->getMessage());
        }
    }

    /**
     * Get admin emails for notifications
     */
    private function getAdminEmails()
    {
        try {
            $stmt = $this->db->prepare("
            SELECT email FROM users u 
            JOIN user_roles ur ON u.user_id = ur.user_id 
            WHERE ur.role_id IN (1, 2) AND u.is_active = 1
        ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting admin emails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get college name by ID
     */
    private function getCollegeName($collegeId)
    {
        try {
            $stmt = $this->db->prepare("SELECT college_name FROM colleges WHERE college_id = :college_id");
            $stmt->execute([':college_id' => $collegeId]);
            return $stmt->fetchColumn() ?: 'Unknown College';
        } catch (PDOException $e) {
            error_log("Error getting college name: " . $e->getMessage());
            return 'Unknown College';
        }
    }

    /**
     * Get department name by ID
     */
    private function getDepartmentName($departmentId)
    {
        try {
            $stmt = $this->db->prepare("SELECT department_name FROM departments WHERE department_id = :department_id");
            $stmt->execute([':department_id' => $departmentId]);
            return $stmt->fetchColumn() ?: 'Unknown Department';
        } catch (PDOException $e) {
            error_log("Error getting department name: " . $e->getMessage());
            return 'Unknown Department';
        }
    }

    /**
     * Get role names by IDs
     */
    public function getRoleNames($roleIds)
    {
        try {
            if (empty($roleIds)) return ['Unknown Role'];

            $placeholders = str_repeat('?,', count($roleIds) - 1) . '?';
            $stmt = $this->db->prepare("SELECT role_name FROM roles WHERE role_id IN ($placeholders)");
            $stmt->execute($roleIds);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting role names: " . $e->getMessage());
            return ['Unknown Role'];
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
            session_start([
                'cookie_lifetime' => 86400,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_httponly' => true,
                'use_strict_mode' => true
            ]);
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['title'] = $user['title'] ?? null;
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['middle_name'] = $user['middle_name'] ?? null;
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['suffix'] = $user['suffix'] ?? null;
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['college_id'] = $user['college_id'];
        $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
        $_SESSION['logged_in'] = true;

        $rolesData = $this->userModel->getUserRoles($user['user_id']);
        $_SESSION['roles'] = array_column($rolesData, 'role_name');

        // Set initial current_role
        $_SESSION['current_role'] = $_SESSION['roles'][0] ?? 'Faculty'; // Default to 'Faculty'

        session_regenerate_id(true);
        error_log("Session started for user_id: {$user['user_id']} with roles: " . json_encode($_SESSION['roles']) . ", current_role: {$_SESSION['current_role']}");
    }

    /**
     * Destroy the current session
     * @return void
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    /**
     * Check if a user is logged in
     * @return bool
     */
    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
}
