<?php
namespace Src\Repositories;

use PDO;
use Exception;

class ClassroomRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ──────────────────────────────────────────────
    // READ
    // ──────────────────────────────────────────────

    /**
     * Fetch all classrooms visible to a department (owned + explicitly shared),
     * with current semester usage and schedule details.
     */
    public function getByDepartment(int $departmentId, int $semesterId): array
    {
        $sql = "
            SELECT 
                c.*,
                d.department_name,
                cl.college_name,
                CASE 
                    WHEN c.department_id = :department_id1 THEN 'Owned'
                    WHEN c.shared = 1 AND cd.department_id IS NOT NULL THEN 'Included'
                    ELSE 'Unknown'
                END AS room_status,
                COUNT(DISTINCT s.schedule_id) AS current_semester_usage,
                GROUP_CONCAT(DISTINCT CONCAT(
                    sec.section_name, '|',
                    COALESCE(crs.course_code, 'N/A'), '|',
                    COALESCE(TRIM(CONCAT(
                        COALESCE(u.title, ''), ' ',
                        COALESCE(u.first_name, ''), ' ',
                        COALESCE(u.middle_name, ''), ' ',
                        COALESCE(u.last_name, ''), ' ',
                        COALESCE(u.suffix, '')
                    )), u.email, 'TBA'), '|',
                    s.day_of_week, '|',
                    s.start_time, '|',
                    s.end_time
                ) SEPARATOR ';;;') AS schedule_details
            FROM classrooms c
            JOIN departments d  ON c.department_id = d.department_id
            JOIN colleges cl    ON d.college_id    = cl.college_id
            LEFT JOIN classroom_departments cd
                ON c.room_id = cd.classroom_id AND cd.department_id = :department_id2
            LEFT JOIN schedules s
                ON c.room_id = s.room_id
               AND s.semester_id = :semester_id
               AND s.room_id IS NOT NULL
            LEFT JOIN sections sec ON s.section_id  = sec.section_id
            LEFT JOIN courses crs  ON s.course_id   = crs.course_id
            LEFT JOIN faculty f    ON s.faculty_id  = f.faculty_id
            LEFT JOIN users u      ON f.user_id     = u.user_id
            WHERE (
                c.department_id = :department_id3
                OR (c.shared = 1 AND cd.department_id = :department_id4)
            )
            GROUP BY c.room_id
            ORDER BY c.room_name
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id1' => $departmentId,
            ':department_id2' => $departmentId,
            ':department_id3' => $departmentId,
            ':department_id4' => $departmentId,
            ':semester_id'    => $semesterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single classroom's full schedule for the given semester,
     * grouped by day of week.
     */
    public function getScheduleByRoom(int $roomId, int $semesterId): array
    {
        $sql = "
            SELECT 
                s.day_of_week,
                s.start_time,
                s.end_time,
                sec.section_name,
                crs.course_code,
                crs.course_name,
                COALESCE(
                    TRIM(CONCAT(
                        COALESCE(u.title, ''), ' ',
                        COALESCE(u.first_name, ''), ' ',
                        COALESCE(u.middle_name, ''), ' ',
                        COALESCE(u.last_name, ''), ' ',
                        COALESCE(u.suffix, '')
                    )),
                    u.email,
                    'TBA'
                ) AS faculty_name,
                c.room_type
            FROM schedules s
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN courses crs  ON s.course_id  = crs.course_id
            LEFT JOIN faculty f    ON s.faculty_id = f.faculty_id
            LEFT JOIN users u      ON f.user_id    = u.user_id
            LEFT JOIN classrooms c ON s.room_id    = c.room_id
            WHERE s.room_id    = :room_id
              AND s.semester_id = :semester_id
            ORDER BY
                FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                s.start_time
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':room_id' => $roomId, ':semester_id' => $semesterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get basic info for a classroom (used for schedule modal header).
     */
    public function getInfo(int $roomId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT c.room_name, c.building, d.department_name
            FROM classrooms c
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE c.room_id = :room_id
        ");
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get department_id owner of a classroom (used for ownership checks).
     */
    public function getOwnerDepartment(int $roomId): int|false
    {
        $stmt = $this->db->prepare("SELECT department_id FROM classrooms WHERE room_id = :room_id");
        $stmt->execute([':room_id' => $roomId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : false;
    }

    /**
     * Check whether a room is already included for a department.
     */
    public function getInclusionId(int $roomId, int $departmentId): int|false
    {
        $stmt = $this->db->prepare("
            SELECT classroom_department_id
            FROM classroom_departments
            WHERE classroom_id = :room_id AND department_id = :department_id
        ");
        $stmt->execute([':room_id' => $roomId, ':department_id' => $departmentId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : false;
    }

    /**
     * Fetch shared rooms (shared=1) not owned by the given department,
     * optionally filtered by a search term.
     */
    public function searchSharedRooms(int $departmentId, string $search = ''): array
    {
        $term = '%' . $search . '%';
        $sql = "
            SELECT 
                c.*,
                d.department_name,
                cl.college_name,
                'Shared' AS room_status
            FROM classrooms c
            JOIN departments d ON c.department_id = d.department_id
            JOIN colleges cl   ON d.college_id    = cl.college_id
            WHERE c.shared = 1
              AND c.department_id != :department_id
              AND (
                  c.room_name      LIKE :search1
                  OR c.building    LIKE :search2
                  OR d.department_name LIKE :search3
              )
            ORDER BY c.room_name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':search1'       => $term,
            ':search2'       => $term,
            ':search3'       => $term,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch labs and shared rooms for availability checking.
     */
    public function getLabsAndSharedRooms(int $departmentId, int $semesterId): array
    {
        $sql = "
            SELECT 
                c.room_id,
                c.room_type,
                c.shared,
                c.availability AS current_availability,
                COUNT(s.schedule_id) AS schedule_count,
                GROUP_CONCAT(s.time_slot) AS time_slots
            FROM classrooms c
            LEFT JOIN classroom_departments cd
                ON c.room_id = cd.classroom_id AND cd.department_id = :department_id3
            JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN schedules s
                ON c.room_id = s.room_id AND s.semester_id = :semester_id
            WHERE (
                c.department_id = :department_id1
                OR (
                    c.shared = 0
                    AND d.college_id = (
                        SELECT college_id FROM departments WHERE department_id = :department_id2
                    )
                )
                OR (c.shared = 1 AND cd.department_id = :department_id3)
            )
            AND (c.room_type = 'laboratory' OR c.shared = 1)
            GROUP BY c.room_id, c.room_type, c.shared, c.current_availability
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id1' => $departmentId,
            ':department_id2' => $departmentId,
            ':department_id3' => $departmentId,
            ':semester_id'    => $semesterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // WRITE
    // ──────────────────────────────────────────────

    /**
     * Insert a new classroom and return its new ID.
     */
    public function create(
        int $departmentId,
        string $roomName,
        string $building,
        int $capacity,
        string $roomType,
        int $shared,
        string $availability
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO classrooms
                (room_name, building, capacity, room_type, shared, availability, department_id, created_at, updated_at)
            VALUES
                (:room_name, :building, :capacity, :room_type, :shared, :availability, :department_id, NOW(), NOW())
        ");
        $stmt->execute([
            ':room_name'     => $roomName,
            ':building'      => $building,
            ':capacity'      => $capacity,
            ':room_type'     => $roomType,
            ':shared'        => $shared,
            ':availability'  => $availability,
            ':department_id' => $departmentId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing classroom (must be owned by the given department).
     */
    public function update(
        int $roomId,
        int $departmentId,
        string $roomName,
        string $building,
        int $capacity,
        string $roomType,
        int $shared,
        string $availability
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE classrooms SET
                room_name    = :room_name,
                building     = :building,
                capacity     = :capacity,
                room_type    = :room_type,
                shared       = :shared,
                availability = :availability,
                updated_at   = NOW()
            WHERE room_id       = :room_id
              AND department_id = :department_id
        ");
        $stmt->execute([
            ':room_name'     => $roomName,
            ':building'      => $building,
            ':capacity'      => $capacity,
            ':room_type'     => $roomType,
            ':shared'        => $shared,
            ':availability'  => $availability,
            ':room_id'       => $roomId,
            ':department_id' => $departmentId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update availability of a single classroom.
     */
    public function updateAvailability(int $roomId, string $availability): bool
    {
        $stmt = $this->db->prepare("
            UPDATE classrooms SET availability = :availability WHERE room_id = :room_id
        ");
        $stmt->execute([':availability' => $availability, ':room_id' => $roomId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Set all relevant classrooms to 'available' at the start of a semester.
     */
    public function setAllAvailableForDepartment(int $departmentId): int
    {
        $sql = "
            UPDATE classrooms c
            LEFT JOIN classroom_departments cd
                ON c.room_id = cd.classroom_id AND cd.department_id = :department_id2
            SET c.availability = 'available'
            WHERE (
                c.department_id = :department_id1
                OR (c.shared = 1 AND cd.department_id = :department_id2)
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id1' => $departmentId,
            ':department_id2' => $departmentId,
        ]);
        return $stmt->rowCount();
    }

    /**
     * Add a shared room to a department's list.
     * Returns the new classroom_department_id.
     */
    public function includeRoom(int $roomId, int $departmentId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO classroom_departments (classroom_id, department_id, created_at)
            VALUES (:room_id, :department_id, NOW())
        ");
        $stmt->execute([':room_id' => $roomId, ':department_id' => $departmentId]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Remove a shared room from a department's list by classroom_department_id.
     */
    public function excludeRoom(int $classroomDepartmentId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM classroom_departments WHERE classroom_department_id = :id
        ");
        $stmt->execute([':id' => $classroomDepartmentId]);
        return $stmt->rowCount() > 0;
    }

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    /**
     * Group a flat schedule rows array into ['Monday' => [...], ...]
     * with normalized time/course/section/faculty/type fields.
     */
    public static function groupScheduleByDay(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $day = $row['day_of_week'];
            if (!isset($grouped[$day])) {
                $grouped[$day] = [];
            }
            $grouped[$day][] = [
                'time'    => date('h:i A', strtotime($row['start_time']))
                    . ' - '
                    . date('h:i A', strtotime($row['end_time'])),
                'course'  => $row['course_code'] . ' - ' . $row['course_name'],
                'section' => $row['section_name'],
                'faculty' => $row['faculty_name'],
                'type'    => $row['room_type'] === 'laboratory' ? 'Lab' : 'Lecture',
            ];
        }
        return $grouped;
    }
}
