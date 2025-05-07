<?php
require_once __DIR__ . '/../config/database.php';

class ApiController
{
    protected $db;

    public function __construct()
    {
        try {
            $this->db = (new Database())->connect();
            if ($this->db === null) {
                error_log("Failed to connect to the database in ApiController");
                die("Database connection failed. Please try again later.");
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("ApiController::construct: Database connection failed - " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    protected function getDepartmentId($userId)
    {
        $stmt = $this->db->prepare("SELECT department_id FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['department_id'] ?? null;
    }

    public function loadData()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Adjust for security

        // Check if user is authenticated and has correct role
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [4, 5, 6])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        $type = isset($_GET['type']) ? trim($_GET['type']) : '';
        if (empty($type) || !in_array($type, ['faculty', 'course'])) {
            error_log("ApiController::loadData: Invalid or missing type parameter: $type");
            echo json_encode(['error' => 'Invalid request type']);
            exit;
        }

        try {
            $departmentId = $this->getDepartmentId($_SESSION['user_id']);
            if (!$departmentId) {
                error_log("ApiController::loadData: No department found for user_id: " . $_SESSION['user_id']);
                echo json_encode(['error' => 'No department assigned']);
                exit;
            }

            $data = [];
            if ($type === 'faculty') {
                $stmt = $this->db->prepare("SELECT f.faculty_id AS id, CONCAT(u.first_name, ' ', u.last_name) AS name 
                                            FROM faculty f 
                                            JOIN users u ON f.user_id = u.user_id 
                                            WHERE u.department_id = :department_id");
                $stmt->execute([':department_id' => $departmentId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($type === 'course') {
                $stmt = $this->db->prepare("SELECT course_id AS id, course_code AS code, course_name AS name 
                                            FROM courses 
                                            WHERE department_id = :department_id");
                $stmt->execute([':department_id' => $departmentId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($data)) {
                error_log("ApiController::loadData: No data found for type: $type");
                echo json_encode([]);
            } else {
                echo json_encode($data);
            }
        } catch (PDOException $e) {
            error_log("ApiController::loadData: Database error - " . $e->getMessage());
            echo json_encode(['error' => 'Failed to load data']);
        } catch (Exception $e) {
            error_log("ApiController::loadData: General error - " . $e->getMessage());
            echo json_encode(['error' => 'An unexpected error occurred']);
        }
        exit;
    }
}
