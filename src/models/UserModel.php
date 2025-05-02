<?php
require_once __DIR__ . '/../config/Database.php';

class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Fetch user details by user ID
     * @param int $userId
     * @return array
     */
    public function getUserById($userId)
    {
        try {
            $query = "
                SELECT u.user_id, u.employee_id, u.username, u.email, u.first_name, u.middle_name, 
                       u.last_name, u.suffix, u.profile_picture, u.is_active,
                       r.role_name, d.department_name, c.college_name
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                JOIN colleges c ON u.college_id = c.college_id
                WHERE u.user_id = :userId AND u.is_active = 1
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch users by role name
     * @param string $roleName
     * @return array
     */
    public function getUsersByRole($roleName)
    {
        try {
            $query = "
                SELECT u.user_id, u.employee_id, u.username, u.email, u.first_name, u.middle_name, 
                       u.last_name, u.suffix, u.profile_picture, u.is_active,
                       r.role_name, d.department_name, c.college_name
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                JOIN colleges c ON u.college_id = c.college_id
                WHERE r.role_name = :roleName AND u.is_active = 1
                ORDER BY u.last_name, u.first_name
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':roleName', $roleName, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users by role: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch faculty details by user ID
     * @param int $userId
     * @return array
     */
    public function getFacultyDetails($userId)
    {
        try {
            $query = "
                SELECT f.faculty_id, f.user_id, f.employee_id, f.academic_rank, f.employment_type, 
                       f.classification, f.max_hours, d.department_name, 
                       p1.program_name AS primary_program, p2.program_name AS secondary_program
                FROM faculty f
                JOIN departments d ON f.department_id = d.department_id
                LEFT JOIN programs p1 ON f.primary_program_id = p1.program_id
                LEFT JOIN programs p2 ON f.secondary_program_id = p2.program_id
                WHERE f.user_id = :userId
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($faculty) {
                // Fetch specializations
                $faculty['specializations'] = $this->getFacultySpecializations($faculty['faculty_id']);
            }

            return $faculty ?: [];
        } catch (PDOException $e) {
            error_log("Error fetching faculty details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch specializations for a faculty member
     * @param int $facultyId
     * @return array
     */
    private function getFacultySpecializations($facultyId)
    {
        try {
            $query = "
                SELECT s.specialization_id, s.subject_name, s.expertise_level, 
                       p.program_name, s.is_primary_specialization
                FROM specializations s
                LEFT JOIN programs p ON s.program_id = p.program_id
                WHERE s.faculty_id = :facultyId
                ORDER BY s.is_primary_specialization DESC, s.subject_name
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':facultyId', $facultyId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching faculty specializations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new user
     * @param array $data
     * @return bool
     */
    public function createUser($data)
    {
        try {
            $query = "
                INSERT INTO users (
                    employee_id, username, password_hash, email, phone, first_name, middle_name,
                    last_name, suffix, profile_picture, role_id, department_id, college_id, is_active
                ) VALUES (
                    :employee_id, :username, :password_hash, :email, :phone, :first_name, :middle_name,
                    :last_name, :suffix, :profile_picture, :role_id, :department_id, :college_id, :is_active
                )
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':employee_id' => $data['employee_id'],
                ':username' => $data['username'],
                ':password_hash' => $data['password_hash'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':first_name' => $data['first_name'],
                ':middle_name' => $data['middle_name'] ?? null,
                ':last_name' => $data['last_name'],
                ':suffix' => $data['suffix'] ?? null,
                ':profile_picture' => $data['profile_picture'] ?? null,
                ':role_id' => $data['role_id'],
                ':department_id' => $data['department_id'],
                ':college_id' => $data['college_id'],
                ':is_active' => $data['is_active']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a faculty record
     * @param array $data
     * @return bool
     */
    public function createFaculty($data)
    {
        try {
            $query = "
            INSERT INTO faculty (
                user_id, employee_id, academic_rank, employment_type, department_id, primary_program_id
            ) VALUES (
                :user_id, :employee_id, :academic_rank, :employment_type, :department_id, :primary_program_id
            )
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':employee_id' => $data['employee_id'],
                ':academic_rank' => $data['academic_rank'],
                ':employment_type' => $data['employment_type'],
                ':department_id' => $data['department_id'],
                ':primary_program_id' => $data['primary_program_id']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating faculty: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a dean record
     * @param array $data
     * @return bool
     */
    public function createDean($data)
    {
        try {
            $query = "
                INSERT INTO deans (user_id, college_id, start_date, is_current)
                VALUES (:user_id, :college_id, :start_date, 1)
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':college_id' => $data['college_id'],
                ':start_date' => $data['start_date']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating dean: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a program chair record
     * @param array $data
     * @return bool
     */
    public function createProgramChair($data)
    {
        try {
            $query = "
                INSERT INTO program_chairs (user_id, program_id, start_date, is_current)
                VALUES (:user_id, :program_id, :start_date, 1)
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':program_id' => $data['program_id'],
                ':start_date' => $data['start_date']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating program chair: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a department instructor record
     * @param array $data
     * @return bool
     */
    public function createDepartmentInstructor($data)
    {
        try {
            $query = "
                INSERT INTO department_instructors (user_id, department_id, start_date, is_current)
                VALUES (:user_id, :department_id, :start_date, 1)
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':department_id' => $data['department_id'],
                ':start_date' => $data['start_date']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating department instructor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing user
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateUser($userId, $data)
    {
        try {
            $query = "
                UPDATE users
                SET employee_id = :employee_id,
                    username = :username,
                    email = :email,
                    phone = :phone,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    suffix = :suffix,
                    profile_picture = :profile_picture,
                    role_id = :role_id,
                    department_id = :department_id,
                    college_id = :college_id,
                    is_active = :is_active
                WHERE user_id = :user_id
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_STR);
            $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $data['first_name'], PDO::PARAM_STR);
            $stmt->bindParam(':middle_name', $data['middle_name'], PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $data['last_name'], PDO::PARAM_STR);
            $stmt->bindParam(':suffix', $data['suffix'], PDO::PARAM_STR);
            $stmt->bindParam(':profile_picture', $data['profile_picture'], PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $data['role_id'], PDO::PARAM_INT);
            $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
            $stmt->bindParam(':college_id', $data['college_id'], PDO::PARAM_INT);
            $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user (soft delete by setting is_active to 0)
     * @param int $userId
     * @return bool
     */
    public function deleteUser($userId)
    {
        try {
            $query = "UPDATE users SET is_active = 0 WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add faculty specialization
     * @param array $data
     * @return bool
     */
    public function addFacultySpecialization($data)
    {
        try {
            $query = "
                INSERT INTO specializations (
                    faculty_id, subject_name, expertise_level, program_id, is_primary_specialization
                ) VALUES (
                    :faculty_id, :subject_name, :expertise_level, :program_id, :is_primary_specialization
                )
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':faculty_id', $data['faculty_id'], PDO::PARAM_INT);
            $stmt->bindParam(':subject_name', $data['subject_name'], PDO::PARAM_STR);
            $stmt->bindParam(':expertise_level', $data['expertise_level'], PDO::PARAM_STR);
            $stmt->bindParam(':program_id', $data['program_id'], PDO::PARAM_INT);
            $stmt->bindParam(':is_primary_specialization', $data['is_primary_specialization'], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding faculty specialization: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all roles
     * @return array
     */
    public function getRoles()
    {
        try {
            $query = "SELECT role_id, role_name FROM roles ORDER BY role_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all colleges
     * @return array
     */
    public function getColleges()
    {
        try {
            $query = "SELECT college_id, college_name FROM colleges ORDER BY college_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching colleges: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get departments by college
     * @param int $collegeId
     * @return array
     */
    public function getDepartmentsByCollege($collegeId)
    {
        try {
            $query = "SELECT department_id, department_name 
                  FROM departments 
                  WHERE college_id = :college_id 
                  ORDER BY department_name";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':college_id', $collegeId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching departments: " . $e->getMessage());
            return [];
        }
    }
}
