<?php
namespace Src\Repositories;

use PDO;
use PDOException;

class ScheduleRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ──────────────────────────────────────────────
    // READ
    // ──────────────────────────────────────────────

    public function getByDepartmentAndSemester(int $departmentId, int $semesterId): array
    {
        $sql = "
            SELECT 
                s.*,
                c.course_code,
                c.course_name,
                sec.section_name,
                sec.year_level,
                sec.department_id,
                CONCAT(COALESCE(u.title,''),' ',u.first_name,' ',
                       COALESCE(u.middle_name,''),' ',u.last_name,' ',
                       COALESCE(u.suffix,'')) AS faculty_name,
                r.room_name
            FROM schedules s
            JOIN courses c   ON s.course_id  = c.course_id
            JOIN sections sec ON s.section_id = sec.section_id
            JOIN faculty f   ON s.faculty_id  = f.faculty_id
            JOIN users u     ON f.user_id     = u.user_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE sec.department_id = :department_id
              AND s.semester_id     = :semester_id
            ORDER BY
                sec.year_level,
                sec.section_name,
                FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
                s.start_time
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':semester_id'   => $semesterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $scheduleId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                c.course_code, c.course_name,
                sec.section_name, sec.year_level,
                CONCAT(u.first_name,' ',u.last_name) AS faculty_name,
                r.room_name,
                sem.semester_name, sem.academic_year
            FROM schedules s
            JOIN courses c     ON s.course_id  = c.course_id
            JOIN sections sec  ON s.section_id = sec.section_id
            JOIN faculty f     ON s.faculty_id = f.faculty_id
            JOIN users u       ON f.user_id    = u.user_id
            LEFT JOIN classrooms r  ON s.room_id    = r.room_id
            JOIN semesters sem ON s.semester_id = sem.semester_id
            WHERE s.schedule_id = :schedule_id
        ");
        $stmt->execute([':schedule_id' => $scheduleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifyOwnership(int $scheduleId, int $departmentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT s.schedule_id
            FROM schedules s
            JOIN sections sec ON s.section_id = sec.section_id
            WHERE s.schedule_id    = :schedule_id
              AND sec.department_id = :department_id
        ");
        $stmt->execute([
            ':schedule_id'   => $scheduleId,
            ':department_id' => $departmentId,
        ]);
        return $stmt->fetch() !== false;
    }

    public function getConsolidated(int $semesterId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                s.course_id,
                c.course_code,
                c.course_name AS subject_description,
                c.units,
                c.lecture_hours AS lec,
                c.lab_hours AS lab,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        COALESCE(s.day_of_week,'Unknown'),'|',
                        s.start_time,'|',
                        s.end_time,'|',
                        COALESCE(r.room_name,'Online'),'|',
                        COALESCE(s.schedule_type,'F2F')
                    ) ORDER BY
                        FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
                        s.start_time
                    SEPARATOR '||'
                ) AS schedule_details,
                CONCAT(u.first_name,' ',u.last_name) AS instructor,
                GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR ', ') AS sections,
                s.section_id
            FROM schedules s
            JOIN courses c         ON s.course_id  = c.course_id
            JOIN curriculum_courses cd ON c.course_id = cd.course_id
            JOIN sections sec      ON s.section_id  = sec.section_id
            JOIN faculty f         ON s.faculty_id  = f.faculty_id
            JOIN users u           ON f.user_id     = u.user_id
            LEFT JOIN classrooms r ON s.room_id     = r.room_id
            WHERE s.semester_id = :semester_id
            GROUP BY s.course_id, s.faculty_id, s.section_id
            ORDER BY c.course_code
        ");
        $stmt->execute([':semester_id' => $semesterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFacultySchedulesForConflict(
        string $entityField,
        int $entityId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $semesterId,
        ?int $excludeScheduleId = null
    ): array {
        $sql = "
            SELECT
                s.schedule_id,
                c.course_code,
                sec.section_name,
                CONCAT(u.first_name,' ',u.last_name) AS faculty_name,
                r.room_name,
                s.day_of_week,
                s.start_time,
                s.end_time
            FROM schedules s
            JOIN courses c    ON s.course_id  = c.course_id
            JOIN sections sec ON s.section_id = sec.section_id
            JOIN faculty f    ON s.faculty_id = f.faculty_id
            JOIN users u      ON f.user_id    = u.user_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.{$entityField} = :entity_id
              AND s.semester_id    = :semester_id
              AND s.day_of_week    = :day_of_week
              AND (s.start_time < :end_time AND s.end_time > :start_time)
        ";
        $params = [
            ':entity_id'   => $entityId,
            ':semester_id' => $semesterId,
            ':day_of_week' => $dayOfWeek,
            ':start_time'  => $startTime,
            ':end_time'    => $endTime,
        ];
        if ($excludeScheduleId !== null && $excludeScheduleId > 0) {
            $sql .= ' AND s.schedule_id != :exclude_id';
            $params[':exclude_id'] = $excludeScheduleId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForFacultyTeachingLoad(array $facultyIds, int $semesterId): array
    {
        if (empty($facultyIds)) return [];
        $placeholders = str_repeat('?,', count($facultyIds) - 1) . '?';
        $stmt = $this->db->prepare("
            SELECT
                s.faculty_id,
                c.course_code, c.course_name, c.units,
                COALESCE(r.room_name,'Online') AS room_name,
                s.day_of_week, s.start_time, s.end_time,
                COALESCE(s.component_type,'lecture') AS component_type,
                s.schedule_type, s.status,
                COALESCE(sec.section_name,'N/A') AS section_name,
                COALESCE(sec.current_students,0) AS current_students,
                sec.year_level,
                COALESCE(TIMESTAMPDIFF(MINUTE,s.start_time,s.end_time)/60,0) AS duration_hours
            FROM schedules s
            LEFT JOIN courses c    ON s.course_id  = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id    = r.room_id
            WHERE s.faculty_id IN ($placeholders)
              AND s.semester_id = ?
              AND s.status != 'Rejected'
            ORDER BY s.faculty_id, c.course_code, s.component_type, s.start_time
        ");
        $stmt->execute(array_merge($facultyIds, [$semesterId]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // WRITE
    // ──────────────────────────────────────────────

    public function insert(array $data): int
    {
        $componentTypeMap = [
            'lab'        => 'laboratory',
            'lecture'    => 'lecture',
            'laboratory' => 'laboratory',
            'tutorial'   => 'tutorial',
            'recitation' => 'recitation',
        ];
        $componentType = isset($data['component_type'])
            ? ($componentTypeMap[strtolower($data['component_type'])] ?? strtolower($data['component_type']))
            : null;

        $stmt = $this->db->prepare("
            INSERT INTO schedules (
                course_id, section_id, room_id, semester_id, faculty_id,
                schedule_type, day_of_week, start_time, end_time,
                status, is_public, department_id, component_type
            ) VALUES (
                :course_id, :section_id, :room_id, :semester_id, :faculty_id,
                :schedule_type, :day_of_week, :start_time, :end_time,
                :status, :is_public, :department_id, :component_type
            )
        ");
        $stmt->execute([
            ':course_id'      => $data['course_id'],
            ':section_id'     => $data['section_id'],
            ':room_id'        => $data['room_id'],
            ':semester_id'    => $data['semester_id'],
            ':faculty_id'     => $data['faculty_id'],
            ':schedule_type'  => $data['schedule_type'],
            ':day_of_week'    => $data['day_of_week'],
            ':start_time'     => $data['start_time'],
            ':end_time'       => $data['end_time'],
            ':status'         => $data['status']      ?? 'Pending',
            ':is_public'      => $data['is_public']   ?? 0,
            ':department_id'  => $data['department_id'],
            ':component_type' => $componentType,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $scheduleId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE schedules SET
                course_id     = :course_id,
                section_id    = :section_id,
                faculty_id    = :faculty_id,
                room_id       = :room_id,
                day_of_week   = :day_of_week,
                start_time    = :start_time,
                end_time      = :end_time,
                schedule_type = :schedule_type,
                updated_at    = NOW()
            WHERE schedule_id = :schedule_id
        ");
        $stmt->execute([
            ':course_id'     => $data['course_id'],
            ':section_id'    => $data['section_id'],
            ':faculty_id'    => $data['faculty_id'],
            ':room_id'       => $data['room_id'],
            ':day_of_week'   => $data['day_of_week'],
            ':start_time'    => $data['start_time'],
            ':end_time'      => $data['end_time'],
            ':schedule_type' => $data['schedule_type'],
            ':schedule_id'   => $scheduleId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateDrag(int $scheduleId, string $day, string $start, string $end): bool
    {
        $stmt = $this->db->prepare("
            UPDATE schedules
            SET day_of_week = :day, start_time = :start, end_time = :end, updated_at = NOW()
            WHERE schedule_id = :id
        ");
        $stmt->execute([':day' => $day, ':start' => $start, ':end' => $end, ':id' => $scheduleId]);
        return $stmt->rowCount() > 0;
    }

    public function updateFaculty(int $scheduleId, int $facultyId): bool
    {
        $stmt = $this->db->prepare("UPDATE schedules SET faculty_id = :fid WHERE schedule_id = :sid");
        $stmt->execute([':fid' => $facultyId, ':sid' => $scheduleId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteById(int $scheduleId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM schedules WHERE schedule_id = :id");
        $stmt->execute([':id' => $scheduleId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByDepartment(int $departmentId): int
    {
        $stmt = $this->db->prepare("DELETE FROM schedules WHERE department_id = :department_id");
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->rowCount();
    }

    public function deleteByYearLevels(int $departmentId, int $semesterId, array $yearLevels): int
    {
        $placeholders = implode(',', array_fill(0, count($yearLevels), '?'));
        $sql = "
            DELETE s FROM schedules s
            INNER JOIN sections sec ON s.section_id = sec.section_id
            WHERE sec.department_id = ?
              AND s.semester_id     = ?
              AND sec.year_level IN ($placeholders)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$departmentId, $semesterId], $yearLevels));
        return $stmt->rowCount();
    }

    public function removeDuplicates(int $departmentId, int $semesterId): int
    {
        $sql = "
            DELETE s1 FROM schedules s1
            INNER JOIN (
                SELECT s2.schedule_id
                FROM schedules s2
                INNER JOIN (
                    SELECT course_id, section_id, day_of_week, start_time, end_time, semester_id,
                           MIN(schedule_id) AS keep_id
                    FROM schedules
                    WHERE semester_id    = :inner_sem
                      AND department_id = :inner_dept
                    GROUP BY course_id, section_id, day_of_week, start_time, end_time, semester_id
                    HAVING COUNT(*) > 1
                ) dups ON s2.course_id    = dups.course_id
                       AND s2.section_id  = dups.section_id
                       AND s2.day_of_week = dups.day_of_week
                       AND s2.start_time  = dups.start_time
                       AND s2.end_time    = dups.end_time
                       AND s2.semester_id = dups.semester_id
                WHERE s2.schedule_id != dups.keep_id
            ) dups_to_delete ON s1.schedule_id = dups_to_delete.schedule_id
            WHERE s1.semester_id    = :outer_sem
              AND s1.department_id  = :outer_dept
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':inner_sem'  => $semesterId,
            ':inner_dept' => $departmentId,
            ':outer_sem'  => $semesterId,
            ':outer_dept' => $departmentId,
        ]);
        return $stmt->rowCount();
    }

    public function resetAutoIncrement(): void
    {
        $this->db->exec("ALTER TABLE schedules AUTO_INCREMENT = 1");
    }
}
