<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/database.php';

class SchedulingService
{
    private $conn;
    private $db;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->db = (new Database())->connect();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Main API endpoint handler
     */
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

        try {
            switch ($method) {
                case 'GET':
                    $this->handleGetRequest($endpoint);
                    break;
                case 'POST':
                    $this->handlePostRequest($endpoint);
                    break;
                case 'PUT':
                    $this->handlePutRequest($endpoint);
                    break;
                case 'DELETE':
                    $this->handleDeleteRequest($endpoint);
                    break;
                default:
                    throw new Exception("Method not allowed", 405);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle GET requests
     */
    private function handleGetRequest($endpoint)
    {
        switch ($endpoint) {
            case 'available-rooms':
                $this->getAvailableRooms();
                break;
            case 'faculty-schedule':
                $this->getFacultySchedule();
                break;
            case 'room-schedule':
                $this->getRoomSchedule();
                break;
            case 'course-offerings':
                $this->getCourseOfferings();
                break;
            case 'conflicts':
                $this->getConflicts();
                break;
            default:
                throw new Exception("Endpoint not found", 404);
        }
    }

    /**
     * Handle POST requests
     */
    private function handlePostRequest($endpoint)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($endpoint) {
            case 'create-schedule':
                $this->createSchedule($data);
                break;
            case 'assign-faculty':
                $this->assignFaculty($data);
                break;
            case 'reserve-room':
                $this->reserveRoom($data);
                break;
            default:
                throw new Exception("Endpoint not found", 404);
        }
    }

    /**
     * Handle PUT requests
     */
    private function handlePutRequest($endpoint)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($endpoint) {
            case 'update-schedule':
                $this->updateSchedule($data);
                break;
            case 'approve-schedule':
                $this->approveSchedule($data);
                break;
            default:
                throw new Exception("Endpoint not found", 404);
        }
    }

    /**
     * Handle DELETE requests
     */
    private function handleDeleteRequest($endpoint)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($endpoint) {
            case 'delete-schedule':
                $this->deleteSchedule($data);
                break;
            default:
                throw new Exception("Endpoint not found", 404);
        }
    }

    // ======================== GET Endpoint Implementations ========================

    /**
     * Get available rooms based on filters
     */
    private function getAvailableRooms()
    {
        $building = $_GET['building'] ?? null;
        $capacity = $_GET['capacity'] ?? null;
        $roomType = $_GET['roomType'] ?? null;
        $date = $_GET['date'] ?? null;
        $startTime = $_GET['startTime'] ?? null;
        $endTime = $_GET['endTime'] ?? null;

        $query = "SELECT * FROM classrooms WHERE availability = 'available'";

        if ($building) {
            $query .= " AND building = ?";
            $params[] = $building;
        }

        if ($capacity) {
            $query .= " AND capacity >= ?";
            $params[] = $capacity;
        }

        if ($roomType) {
            $query .= " AND room_type = ?";
            $params[] = $roomType;
        }

        $stmt = $this->conn->prepare($query);

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = $result->fetch_all(MYSQLI_ASSOC);

        // If date/time parameters are provided, filter out rooms with conflicts
        if ($date && $startTime && $endTime) {
            $dayOfWeek = date('l', strtotime($date));
            $conflictQuery = "SELECT room_id FROM schedules 
                            WHERE day_of_week = ? 
                            AND ((start_time <= ? AND end_time >= ?) 
                            OR (start_time <= ? AND end_time >= ?) 
                            OR (start_time >= ? AND end_time <= ?))";

            $conflictStmt = $this->conn->prepare($conflictQuery);
            $conflictStmt->bind_param(
                'sssssss',
                $dayOfWeek,
                $startTime,
                $startTime,
                $endTime,
                $endTime,
                $startTime,
                $endTime
            );
            $conflictStmt->execute();
            $conflictResult = $conflictStmt->get_result();
            $conflictingRooms = $conflictResult->fetch_all(MYSQLI_ASSOC);

            $conflictingRoomIds = array_column($conflictingRooms, 'room_id');

            $rooms = array_filter($rooms, function ($room) use ($conflictingRoomIds) {
                return !in_array($room['room_id'], $conflictingRoomIds);
            });
        }

        echo json_encode(array_values($rooms));
    }

    /**
     * Get faculty schedule
     */
    private function getFacultySchedule()
    {
        $facultyId = $_GET['facultyId'] ?? null;
        $semesterId = $_GET['semesterId'] ?? null;

        if (!$facultyId) {
            throw new Exception("Faculty ID is required", 400);
        }

        $query = "SELECT s.*, c.course_code, c.course_name, r.room_name, r.building 
                 FROM schedules s
                 JOIN courses c ON s.course_id = c.course_id
                 LEFT JOIN classrooms r ON s.room_id = r.room_id
                 WHERE s.faculty_id = ?";

        $params = [$facultyId];

        if ($semesterId) {
            $query .= " AND s.semester_id = ?";
            $params[] = $semesterId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode($schedule);
    }

    /**
     * Get room schedule
     */
    private function getRoomSchedule()
    {
        $roomId = $_GET['roomId'] ?? null;
        $semesterId = $_GET['semesterId'] ?? null;

        if (!$roomId) {
            throw new Exception("Room ID is required", 400);
        }

        $query = "SELECT s.*, c.course_code, c.course_name, 
                 CONCAT(u.first_name, ' ', u.last_name) as faculty_name
                 FROM schedules s
                 JOIN courses c ON s.course_id = c.course_id
                 JOIN faculty f ON s.faculty_id = f.faculty_id
                 JOIN users u ON f.user_id = u.user_id
                 WHERE s.room_id = ?";

        $params = [$roomId];

        if ($semesterId) {
            $query .= " AND s.semester_id = ?";
            $params[] = $semesterId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode($schedule);
    }

    /**
     * Get course offerings for a semester
     */
    private function getCourseOfferings()
    {
        $semesterId = $_GET['semesterId'] ?? null;
        $departmentId = $_GET['departmentId'] ?? null;

        if (!$semesterId) {
            throw new Exception("Semester ID is required", 400);
        }

        $query = "SELECT co.*, c.course_code, c.course_name, d.department_name
                 FROM course_offerings co
                 JOIN courses c ON co.course_id = c.course_id
                 JOIN departments d ON c.department_id = d.department_id
                 WHERE co.semester_id = ?";

        $params = [$semesterId];

        if ($departmentId) {
            $query .= " AND c.department_id = ?";
            $params[] = $departmentId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $offerings = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode($offerings);
    }

    /**
     * Get scheduling conflicts
     */
    private function getConflicts()
    {
        $semesterId = $_GET['semesterId'] ?? null;

        if (!$semesterId) {
            throw new Exception("Semester ID is required", 400);
        }

        // Faculty time conflicts
        $facultyConflictsQuery = "SELECT f1.faculty_id, 
                                CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                                f1.day_of_week, f1.start_time, f1.end_time,
                                c1.course_code as course1, c2.course_code as course2
                                FROM schedules f1
                                JOIN schedules f2 ON f1.faculty_id = f2.faculty_id 
                                    AND f1.schedule_id != f2.schedule_id
                                    AND f1.day_of_week = f2.day_of_week
                                    AND ((f1.start_time <= f2.start_time AND f1.end_time > f2.start_time)
                                    OR (f1.start_time < f2.end_time AND f1.end_time >= f2.end_time)
                                    OR (f1.start_time >= f2.start_time AND f1.end_time <= f2.end_time))
                                JOIN courses c1 ON f1.course_id = c1.course_id
                                JOIN courses c2 ON f2.course_id = c2.course_id
                                JOIN faculty fa ON f1.faculty_id = fa.faculty_id
                                JOIN users u ON fa.user_id = u.user_id
                                WHERE f1.semester_id = ? AND f2.semester_id = ?";

        $stmt = $this->conn->prepare($facultyConflictsQuery);
        $stmt->bind_param('ii', $semesterId, $semesterId);
        $stmt->execute();
        $facultyConflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Room time conflicts
        $roomConflictsQuery = "SELECT r1.room_id, cl.room_name, cl.building,
                              r1.day_of_week, r1.start_time, r1.end_time,
                              c1.course_code as course1, c2.course_code as course2
                              FROM schedules r1
                              JOIN schedules r2 ON r1.room_id = r2.room_id 
                                  AND r1.schedule_id != r2.schedule_id
                                  AND r1.day_of_week = r2.day_of_week
                                  AND ((r1.start_time <= r2.start_time AND r1.end_time > r2.start_time)
                                  OR (r1.start_time < r2.end_time AND r1.end_time >= r2.end_time)
                                  OR (r1.start_time >= r2.start_time AND r1.end_time <= r2.end_time))
                              JOIN courses c1 ON r1.course_id = c1.course_id
                              JOIN courses c2 ON r2.course_id = c2.course_id
                              JOIN classrooms cl ON r1.room_id = cl.room_id
                              WHERE r1.semester_id = ? AND r2.semester_id = ?";

        $stmt = $this->conn->prepare($roomConflictsQuery);
        $stmt->bind_param('ii', $semesterId, $semesterId);
        $stmt->execute();
        $roomConflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'facultyConflicts' => $facultyConflicts,
            'roomConflicts' => $roomConflicts
        ]);
    }

    // ======================== POST Endpoint Implementations ========================

    /**
     * Create a new schedule
     */
    private function createSchedule($data)
    {
        $requiredFields = [
            'course_id',
            'section_id',
            'semester_id',
            'faculty_id',
            'schedule_type',
            'day_of_week',
            'start_time',
            'end_time'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        // Check for faculty time conflict
        $conflictCheck = "SELECT 1 FROM schedules 
                         WHERE faculty_id = ? 
                         AND day_of_week = ? 
                         AND semester_id = ?
                         AND ((start_time <= ? AND end_time > ?)
                         OR (start_time < ? AND end_time >= ?)
                         OR (start_time >= ? AND end_time <= ?))";

        $stmt = $this->conn->prepare($conflictCheck);
        $stmt->bind_param(
            'isissssss',
            $data['faculty_id'],
            $data['day_of_week'],
            $data['semester_id'],
            $data['start_time'],
            $data['start_time'],
            $data['end_time'],
            $data['end_time'],
            $data['start_time'],
            $data['end_time']
        );
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Faculty has a scheduling conflict at this time", 409);
        }

        // If room is specified, check for room conflict
        if (isset($data['room_id']) && $data['room_id']) {
            $roomConflictCheck = "SELECT 1 FROM schedules 
                                WHERE room_id = ? 
                                AND day_of_week = ? 
                                AND semester_id = ?
                                AND ((start_time <= ? AND end_time > ?)
                                OR (start_time < ? AND end_time >= ?)
                                OR (start_time >= ? AND end_time <= ?))";

            $stmt = $this->conn->prepare($roomConflictCheck);
            $stmt->bind_param(
                'isissssss',
                $data['room_id'],
                $data['day_of_week'],
                $data['semester_id'],
                $data['start_time'],
                $data['start_time'],
                $data['end_time'],
                $data['end_time'],
                $data['start_time'],
                $data['end_time']
            );
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Room is already booked at this time", 409);
            }
        }

        // Insert the new schedule
        $insertQuery = "INSERT INTO schedules 
                       (course_id, section_id, room_id, semester_id, faculty_id, 
                       schedule_type, day_of_week, start_time, end_time, status, is_public) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 0)";

        $stmt = $this->conn->prepare($insertQuery);
        $stmt->bind_param(
            'iiiiissss',
            $data['course_id'],
            $data['section_id'],
            $data['room_id'] ?? null,
            $data['semester_id'],
            $data['faculty_id'],
            $data['schedule_type'],
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create schedule: " . $stmt->error, 500);
        }

        $scheduleId = $stmt->insert_id;

        // Log the activity
        $this->logActivity(
            $_SESSION['user_id'] ?? null,
            'create_schedule',
            "Created new schedule ID $scheduleId",
            'schedules',
            $scheduleId
        );

        echo json_encode([
            'success' => true,
            'schedule_id' => $scheduleId
        ]);
    }

    /**
     * Assign faculty to a course offering
     */
    private function assignFaculty($data)
    {
        $requiredFields = ['offering_id', 'faculty_id', 'section_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        // Check faculty load
        $loadQuery = "SELECT SUM(c.lecture_hours + c.lab_hours) as current_load
                     FROM teaching_loads tl
                     JOIN course_offerings co ON tl.offering_id = co.offering_id
                     JOIN courses c ON co.course_id = c.course_id
                     WHERE tl.faculty_id = ? AND tl.status = 'Approved'";

        $stmt = $this->conn->prepare($loadQuery);
        $stmt->bind_param('i', $data['faculty_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $currentLoad = $result['current_load'] ?? 0;

        // Get the course hours for this offering
        $courseQuery = "SELECT c.lecture_hours + c.lab_hours as course_hours
                       FROM course_offerings co
                       JOIN courses c ON co.course_id = c.course_id
                       WHERE co.offering_id = ?";

        $stmt = $this->conn->prepare($courseQuery);
        $stmt->bind_param('i', $data['offering_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $courseHours = $result['course_hours'] ?? 0;

        // Check if this would exceed faculty max hours
        $facultyQuery = "SELECT max_hours FROM faculty WHERE faculty_id = ?";
        $stmt = $this->conn->prepare($facultyQuery);
        $stmt->bind_param('i', $data['faculty_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $maxHours = $result['max_hours'] ?? 18;

        if (($currentLoad + $courseHours) > $maxHours) {
            throw new Exception("This assignment would exceed faculty's maximum teaching load", 400);
        }

        // Insert the teaching load
        $insertQuery = "INSERT INTO teaching_loads 
                       (faculty_id, offering_id, section_id, assigned_hours, status) 
                       VALUES (?, ?, ?, ?, 'Proposed')";

        $stmt = $this->conn->prepare($insertQuery);
        $stmt->bind_param(
            'iiii',
            $data['faculty_id'],
            $data['offering_id'],
            $data['section_id'],
            $courseHours
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to assign faculty: " . $stmt->error, 500);
        }

        // Log the activity
        $this->logActivity(
            $_SESSION['user_id'] ?? null,
            'assign_faculty',
            "Assigned faculty ID {$data['faculty_id']} to offering ID {$data['offering_id']}",
            'teaching_loads',
            $stmt->insert_id
        );

        echo json_encode(['success' => true]);
    }

    /**
     * Reserve a room for a special event
     */
    private function reserveRoom($data)
    {
        $requiredFields = ['room_id', 'event_name', 'date', 'start_time', 'end_time'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        // Convert date to day of week
        $dayOfWeek = date('l', strtotime($data['date']));

        // Check for room conflict
        $conflictCheck = "SELECT 1 FROM schedules 
                         WHERE room_id = ? 
                         AND day_of_week = ? 
                         AND ((start_time <= ? AND end_time > ?)
                         OR (start_time < ? AND end_time >= ?)
                         OR (start_time >= ? AND end_time <= ?))";

        $stmt = $this->conn->prepare($conflictCheck);
        $stmt->bind_param(
            'isssssss',
            $data['room_id'],
            $dayOfWeek,
            $data['start_time'],
            $data['start_time'],
            $data['end_time'],
            $data['end_time'],
            $data['start_time'],
            $data['end_time']
        );
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Room is already booked at this time", 409);
        }

        // Check for existing reservation conflict
        $reservationConflict = "SELECT 1 FROM room_reservations 
                               WHERE room_id = ? 
                               AND date = ? 
                               AND ((start_time <= ? AND end_time > ?)
                               OR (start_time < ? AND end_time >= ?)
                               OR (start_time >= ? AND end_time <= ?))
                               AND approval_status = 'Approved'";

        $stmt = $this->conn->prepare($reservationConflict);
        $stmt->bind_param(
            'isssssss',
            $data['room_id'],
            $data['date'],
            $data['start_time'],
            $data['start_time'],
            $data['end_time'],
            $data['end_time'],
            $data['start_time'],
            $data['end_time']
        );
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Room is already reserved at this time", 409);
        }

        // Insert the reservation
        $insertQuery = "INSERT INTO room_reservations 
                       (room_id, reserved_by, event_name, description, 
                       date, start_time, end_time, approval_status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";

        $stmt = $this->conn->prepare($insertQuery);
        $stmt->bind_param(
            'iisssss',
            $data['room_id'],
            $_SESSION['user_id'] ?? null,
            $data['event_name'],
            $data['description'] ?? null,
            $data['date'],
            $data['start_time'],
            $data['end_time']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to reserve room: " . $stmt->error, 500);
        }

        // Log the activity
        $this->logActivity(
            $_SESSION['user_id'] ?? null,
            'room_reservation',
            "Reserved room ID {$data['room_id']} for {$data['event_name']}",
            'room_reservations',
            $stmt->insert_id
        );

        echo json_encode([
            'success' => true,
            'reservation_id' => $stmt->insert_id
        ]);
    }

    // ======================== PUT Endpoint Implementations ========================

    /**
     * Update an existing schedule
     */
    private function updateSchedule($data)
    {
        $requiredFields = ['schedule_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        // Get current schedule data
        $currentQuery = "SELECT * FROM schedules WHERE schedule_id = ?";
        $stmt = $this->conn->prepare($currentQuery);
        $stmt->bind_param('i', $data['schedule_id']);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();

        if (!$current) {
            throw new Exception("Schedule not found", 404);
        }

        // Check if schedule is already approved
        if ($current['status'] == 'Approved') {
            throw new Exception("Cannot modify an approved schedule. Create a change request instead.", 400);
        }

        // Build update query based on provided fields
        $updates = [];
        $params = [];
        $types = '';

        $updatableFields = [
            'course_id',
            'section_id',
            'room_id',
            'semester_id',
            'faculty_id',
            'schedule_type',
            'day_of_week',
            'start_time',
            'end_time',
            'is_public'
        ];

        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
                $types .= $this->getParamType($data[$field]);
            }
        }

        if (empty($updates)) {
            throw new Exception("No fields to update", 400);
        }

        // Add schedule_id to params
        $params[] = $data['schedule_id'];
        $types .= 'i';

        $updateQuery = "UPDATE schedules SET " . implode(', ', $updates) . " WHERE schedule_id = ?";
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update schedule: " . $stmt->error, 500);
        }

        // Log the activity
        $this->logActivity(
            $_SESSION['user_id'] ?? null,
            'update_schedule',
            "Updated schedule ID {$data['schedule_id']}",
            'schedules',
            $data['schedule_id']
        );

        echo json_encode(['success' => true]);
    }

    /**
     * Approve a schedule
     */
    private function approveSchedule($data)
    {
        $requiredFields = ['schedule_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        // Check if user has approval permissions (simplified - in real app would check role)
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Unauthorized", 401);
        }

        $updateQuery = "UPDATE schedules 
                       SET status = 'Approved', 
                           approved_by = ?,
                           approval_date = NOW() 
                       WHERE schedule_id = ?";

        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param('ii', $_SESSION['user_id'], $data['schedule_id']);

        if (!$stmt->execute()) {
            throw new Exception("Failed to approve schedule: " . $stmt->error, 500);
        }

        // Log the activity
        $this->logActivity(
            $_SESSION['user_id'],
            'approve_schedule',
            "Approved schedule ID {$data['schedule_id']}",
            'schedules',
            $data['schedule_id']
        );

        echo json_encode(['success' => true]);
    }

    // ======================== DELETE Endpoint Implementations ========================

    /**
     * Delete a schedule
     */
    private function deleteSchedule($data)
    {
        $requiredFields = ['schedule_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        // Check if schedule exists and is not approved
        $checkQuery = "SELECT status FROM schedules WHERE schedule_id = ?";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bind_param('i', $data['schedule_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            throw new Exception("Schedule not found", 404);
        }

        if ($result['status'] == 'Approved') {
            throw new Exception("Cannot delete an approved schedule", 400);
        }

        // Delete the schedule
        $deleteQuery = "DELETE FROM schedules WHERE schedule_id = ?";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bind_param('i', $data['schedule_id']);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete schedule: " . $stmt->error, 500);
        }

        // Log the activity
        $this->logActivity(
            $_SESSION['user_id'] ?? null,
            'delete_schedule',
            "Deleted schedule ID {$data['schedule_id']}",
            'schedules',
            $data['schedule_id']
        );

        echo json_encode(['success' => true]);
    }

    // ======================== Helper Methods ========================

    /**
     * Log activity to the database
     */
    private function logActivity($userId, $actionType, $description, $entityType = null, $entityId = null)
    {
        $query = "INSERT INTO activity_logs 
                 (user_id, action_type, action_description, entity_type, entity_id) 
                 VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('isssi', $userId, $actionType, $description, $entityType, $entityId);
        $stmt->execute();
    }

    /**
     * Get parameter type for bind_param
     */
    private function getParamType($value)
    {
        if (is_int($value)) return 'i';
        if (is_double($value)) return 'd';
        return 's';
    }
    
}

// Initialize and run the service
$conn = new mysqli("localhost", "username", "password", "acss");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$schedulingService = new SchedulingService($conn);
$schedulingService->handleRequest();

$conn->close();

