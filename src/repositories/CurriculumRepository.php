<?php
namespace Src\Repositories;

use PDO;
use PDOException;

class CurriculumRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ──────────────────────────────────────────────
    // CURRICULA
    // ──────────────────────────────────────────────

    public function getActiveCurricula(int $departmentId): array
    {
        $stmt = $this->db->prepare("
            SELECT curriculum_id, curriculum_name
            FROM curricula
            WHERE department_id = :dept_id AND status = 'Active'
        ");
        $stmt->execute([':dept_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // COURSES
    // ──────────────────────────────────────────────

    public function getCoursesByDepartment(int $departmentId): array
    {
        $stmt = $this->db->prepare("
            SELECT course_id, course_code, course_name, units,
                   lab_units, lecture_units, lab_hours, lecture_hours,
                   COALESCE(subject_type,'Professional Course') AS subject_type
            FROM courses
            WHERE department_id = :department_id
        ");
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCourseById(int $courseId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT course_id, course_code, course_name, units,
                   lab_units, lecture_units, lab_hours, lecture_hours,
                   COALESCE(subject_type,'Professional Course') AS subject_type
            FROM courses
            WHERE course_id = :course_id
        ");
        $stmt->execute([':course_id' => $courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns curriculum courses filtered by semester name.
     * Used by schedule generation and the schedule management UI.
     */
    public function getCurriculumCourses(int $curriculumId, string $semesterName = ''): array
    {
        if (!$curriculumId) return [];

        $sql = "
            SELECT
                c.course_id, c.course_code, c.units,
                c.lecture_units, c.lab_units,
                c.lab_hours, c.lecture_hours, c.course_name,
                cc.subject_type,
                cc.year_level AS curriculum_year,
                cc.curriculum_id,
                cc.semester   AS curriculum_semester,
                cr.curriculum_id AS cr_curriculum_id
            FROM curriculum_courses cc
            JOIN courses c   ON cc.course_id     = c.course_id
            JOIN curricula cr ON cc.curriculum_id = cr.curriculum_id
            WHERE cc.curriculum_id = :curriculum_id
              AND cr.status = 'Active'
        ";
        $params = [':curriculum_id' => $curriculumId];

        if ($semesterName !== '') {
            $sql .= ' AND cc.semester = :semester';
            $params[':semester'] = $semesterName;
        }

        $sql .= "
            ORDER BY
                FIELD(cc.year_level,'1st Year','2nd Year','3rd Year','4th Year'),
                FIELD(cc.semester,'1st','2nd','Mid Year'),
                c.course_code
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // CLASSROOMS
    // ──────────────────────────────────────────────

    public function getAvailableClassrooms(int $departmentId): array
    {
        $stmt = $this->db->prepare("
            SELECT room_id, room_name
            FROM classrooms
            WHERE (department_id = :dept_id OR shared = 1)
              AND availability = 'available'
        ");
        $stmt->execute([':dept_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a room available for a specific day/time slot.
     * Returns the best-fit room or null-room (Online) if none found.
     *
     * @param string $roomPreference  'laboratory' | 'classroom'
     */
    public function findAvailableRoom(
        int $departmentId,
        int $minCapacity,
        string $day,
        string $startTime,
        string $endTime,
        int $semesterId,
        string $roomPreference = 'classroom'
    ): array {
        $busySubquery = "
            NOT EXISTS (
                SELECT 1 FROM schedules s
                WHERE s.room_id    = r.room_id
                  AND s.day_of_week = :day
                  AND NOT (:end_time <= s.start_time OR :start_time >= s.end_time)
                  AND s.semester_id = :semester_id
            )
        ";
        $baseParams = [
            ':capacity'    => $minCapacity,
            ':department_id' => $departmentId,
            ':day'         => $day,
            ':start_time'  => $startTime,
            ':end_time'    => $endTime,
            ':semester_id' => $semesterId,
        ];

        $isLab = $roomPreference === 'laboratory';
        $typeFilter = $isLab
            ? "AND (r.room_type LIKE '%lab%' OR r.room_name LIKE '%lab%')"
            : "AND (r.room_type NOT LIKE '%lab%' AND r.room_name NOT LIKE '%lab%')";

        // Priority 1: owned by department
        $sql = "
            SELECT r.room_id, r.room_name, r.capacity, r.room_type, r.department_id, r.shared
            FROM classrooms r
            WHERE r.capacity      >= :capacity
              AND r.department_id  = :department_id
              AND r.availability   = 'available'
              $typeFilter
              AND $busySubquery
            ORDER BY r.capacity ASC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($baseParams);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($room) return $room;

        // Priority 2: shared rooms
        $sharedParams = $baseParams;
        $sharedParams[':department_id2'] = $departmentId;
        $sql = "
            SELECT r.room_id, r.room_name, r.capacity, r.room_type, r.department_id, r.shared,
                   d.department_name
            FROM classrooms r
            JOIN departments d ON r.department_id = d.department_id
            WHERE r.capacity      >= :capacity
              AND r.department_id != :department_id
              AND r.shared         = 1
              AND r.availability   = 'available'
              $typeFilter
              AND $busySubquery
            ORDER BY r.capacity ASC
            LIMIT 1
        ";
        // reuse same param names — PDO allows this since names are identical
        $stmt = $this->db->prepare($sql);
        $stmt->execute($baseParams);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($room) return $room;

        return ['room_id' => null, 'room_name' => 'Online', 'capacity' => $minCapacity];
    }

    // ──────────────────────────────────────────────
    // FACULTY
    // ──────────────────────────────────────────────

    /**
     * Fetch faculty assigned to a department (for schedule dropdowns).
     */
    public function getFacultyByDepartment(int $departmentId, int $collegeId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CONCAT(COALESCE(u.title,''),' ',u.first_name,' ',
                       COALESCE(u.middle_name,''),' ',u.last_name,' ',
                       COALESCE(u.suffix,'')) AS name,
                f.faculty_id, u.user_id, u.college_id,
                fd.department_id, fd.is_primary,
                COALESCE(fd.is_active,1) AS dept_active,
                CASE WHEN u.college_id != :college_id THEN 1 ELSE 0 END AS is_external_college,
                u.department_id AS user_department_id
            FROM faculty_departments fd
            INNER JOIN faculty f ON fd.faculty_id = f.faculty_id
            INNER JOIN users u   ON f.user_id     = u.user_id
            WHERE fd.department_id = :department_id
              AND (fd.is_active = 1 OR fd.is_active IS NULL)
              AND u.is_active = 1
            ORDER BY
                CASE WHEN u.college_id = :college_id_order THEN 0 ELSE 1 END,
                u.first_name, u.last_name
        ");
        $stmt->execute([
            ':department_id'   => $departmentId,
            ':college_id'      => $collegeId,
            ':college_id_order' => $collegeId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Full faculty profile with specializations — used by the schedule generator.
     */
    public function getFacultySpecializations(int $departmentId, int $collegeId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                f.faculty_id,
                CONCAT(u.first_name,' ',u.last_name) AS faculty_name,
                u.department_id  AS user_department_id,
                u.college_id     AS user_college_id,
                f.classification, f.max_hours, f.academic_rank, f.employment_type,
                fd.is_primary    AS is_department_primary,
                fd.department_id AS assigned_department_id,
                CASE WHEN u.college_id != :college_id THEN 1 ELSE 0 END AS is_external_college
            FROM faculty f
            JOIN users u             ON f.user_id     = u.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            WHERE fd.department_id = :department_id
              AND (fd.is_active = 1 OR fd.is_active IS NULL)
              AND u.is_active = 1
            ORDER BY f.faculty_id
        ");
        $stmt->execute([':department_id' => $departmentId, ':college_id' => $collegeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profiles = [];
        foreach ($rows as $row) {
            $facultyId = (int)$row['faculty_id'];

            // specializations
            $specStmt = $this->db->prepare("SELECT course_id FROM specializations WHERE faculty_id = :fid");
            $specStmt->execute([':fid' => $facultyId]);
            $specializations = array_column($specStmt->fetchAll(PDO::FETCH_ASSOC), 'course_id');

            // all department assignments
            $deptStmt = $this->db->prepare("
                SELECT department_id, is_primary
                FROM faculty_departments
                WHERE faculty_id = :fid AND (is_active = 1 OR is_active IS NULL)
            ");
            $deptStmt->execute([':fid' => $facultyId]);
            $deptRows        = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
            $assignedDepts   = array_column($deptRows, 'department_id');
            $primaryDepts    = array_column(
                array_filter($deptRows, fn($r) => $r['is_primary'] == 1),
                'department_id'
            );

            $isExternal      = (bool)$row['is_external_college'];
            $isExternalDept  = !$isExternal && ((int)$row['user_department_id'] !== $departmentId);

            $canTeachProfessional = in_array($departmentId, $assignedDepts) && !$isExternal;
            $canTeachGeneral      = $isExternal
                || $isExternalDept
                || !in_array($departmentId, $primaryDepts);

            $profiles[] = [
                'faculty_id'             => $facultyId,
                'faculty_name'           => $row['faculty_name'],
                'faculty_primary_department' => $row['user_department_id'],
                'faculty_primary_college'    => $row['user_college_id'],
                'classification'         => $row['classification'] ?? 'VSL',
                'max_hours'              => $row['max_hours'] ?? 18,
                'academic_rank'          => $row['academic_rank'],
                'employment_type'        => $row['employment_type'],
                'assigned_departments'   => $assignedDepts,
                'primary_departments'    => $primaryDepts,
                'specializations'        => $specializations,
                'is_department_primary'  => (bool)$row['is_department_primary'],
                'is_external_college'    => $isExternal,
                'faculty_type'           => $isExternal ? 'EXTERNAL_COLLEGE' : ($isExternalDept ? 'EXTERNAL_DEPARTMENT' : 'INTERNAL'),
                'can_teach_professional' => $canTeachProfessional,
                'can_teach_general'      => $canTeachGeneral,
            ];
        }
        return $profiles;
    }

    /**
     * Fetch active sections for a department + semester (for schedule dropdowns).
     */
    public function getSections(int $departmentId, int $semesterId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.section_id, s.section_name, s.year_level, s.semester_id,
                   s.current_students, s.max_students, s.semester, s.academic_year
            FROM sections s
            WHERE s.department_id = :department_id
              AND s.semester_id   = :semester_id
            ORDER BY
                FIELD(s.year_level,'1st Year','2nd Year','3rd Year','4th Year'),
                s.section_name
        ");
        $stmt->execute([':department_id' => $departmentId, ':semester_id' => $semesterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // DEADLINES
    // ──────────────────────────────────────────────

    public function getActiveDeadline(int $departmentId): string|false
    {
        $stmt = $this->db->prepare("
            SELECT deadline FROM schedule_deadlines
            WHERE department_id = :department_id AND is_active = 1
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchColumn();
    }

    // ──────────────────────────────────────────────
    // FACULTY TEACHING LOAD
    // ──────────────────────────────────────────────

    public function getFacultyTeachingLoadSummary(int $departmentId, int $semesterId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                f.faculty_id,
                f.academic_rank, f.employment_type,
                COALESCE(f.equiv_teaching_load,0) AS equiv_teaching_load,
                f.bachelor_degree, f.master_degree, f.doctorate_degree,
                f.post_doctorate_degree, f.designation, f.classification, f.advisory_class,
                CONCAT(COALESCE(u.title,''),' ',u.first_name,' ',
                       COALESCE(u.middle_name,''),' ',u.last_name,' ',
                       COALESCE(u.suffix,'')) AS faculty_name,
                d.department_name, d.department_id,
                COUNT(DISTINCT s.schedule_id) AS total_schedules,
                COUNT(DISTINCT s.course_id)   AS total_courses,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE,s.start_time,s.end_time)/60),0) AS total_hours,
                COALESCE(SUM(CASE
                    WHEN COALESCE(s.component_type,'lecture') = 'lecture'
                    THEN TIMESTAMPDIFF(MINUTE,s.start_time,s.end_time)/60
                    ELSE 0 END),0) AS lecture_hours,
                COALESCE(SUM(CASE
                    WHEN s.component_type = 'laboratory'
                    THEN TIMESTAMPDIFF(MINUTE,s.start_time,s.end_time)/60
                    ELSE 0 END),0) AS lab_hours,
                COUNT(DISTINCT CASE
                    WHEN COALESCE(s.component_type,'lecture') = 'lecture'
                    THEN s.course_id END) AS lecture_preparations,
                COUNT(DISTINCT CASE
                    WHEN s.component_type = 'laboratory'
                    THEN s.course_id END) AS lab_preparations
            FROM faculty f
            JOIN users u               ON f.user_id     = u.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            JOIN departments d         ON fd.department_id = d.department_id
            LEFT JOIN schedules s
                ON f.faculty_id  = s.faculty_id
               AND s.semester_id = :semester_id
               AND s.status     != 'Rejected'
            WHERE d.department_id = :department_id
            GROUP BY f.faculty_id, u.first_name, u.middle_name, u.last_name, u.title, u.suffix
            ORDER BY faculty_name
        ");
        $stmt->execute([':semester_id' => $semesterId, ':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
