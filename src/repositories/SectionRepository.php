<?php

namespace Src\Repositories;

use PDO;
use PDOException;

class SectionRepository
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
     * Fetch all active sections for a department + semester, ordered by year level.
     */
    public function getSectionsBySemester(int $departmentId, int $semesterId): array
    {
        $sql = "
            SELECT s.*, p.program_name
            FROM sections s
            JOIN programs p ON s.department_id = p.department_id
            WHERE s.department_id = :department_id
              AND s.semester_id   = :semester_id
              AND s.is_active     = 1
            ORDER BY
                CASE s.year_level
                    WHEN '1st Year' THEN 1
                    WHEN '2nd Year' THEN 2
                    WHEN '3rd Year' THEN 3
                    WHEN '4th Year' THEN 4
                    ELSE 5
                END,
                s.section_name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':semester_id'   => $semesterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch sections from all semesters EXCEPT the given one (for the history panel).
     */
    public function getPreviousSections(int $departmentId, int $excludeSemesterId): array
    {
        $sql = "
            SELECT s.*, p.program_name, sm.semester_name, sm.academic_year
            FROM sections s
            JOIN programs  p  ON s.department_id = p.department_id
            JOIN semesters sm ON s.semester_id   = sm.semester_id
            WHERE s.department_id = :department_id
              AND s.semester_id  != :current_semester_id
            ORDER BY
                sm.academic_year DESC,
                CASE sm.semester_name
                    WHEN '1st'     THEN 1
                    WHEN '2nd'     THEN 2
                    WHEN 'Summer'  THEN 3
                    WHEN 'Mid Year'THEN 4
                    ELSE 5
                END,
                CASE s.year_level
                    WHEN '1st Year' THEN 1
                    WHEN '2nd Year' THEN 2
                    WHEN '3rd Year' THEN 3
                    WHEN '4th Year' THEN 4
                    ELSE 5
                END,
                s.section_name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id'      => $departmentId,
            ':current_semester_id' => $excludeSemesterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single section by ID + department (ownership check included).
     */
    public function findActiveById(int $sectionId, int $departmentId): array|false
    {
        $sql = "
            SELECT *
            FROM sections
            WHERE section_id    = :section_id
              AND department_id = :department_id
              AND is_active     = 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':section_id'    => $sectionId,
            ':department_id' => $departmentId,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a section by ID + department regardless of is_active (used for reuse).
     */
    public function findById(int $sectionId, int $departmentId): array|false
    {
        $sql = "
            SELECT *
            FROM sections
            WHERE section_id    = :section_id
              AND department_id = :department_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':section_id'    => $sectionId,
            ':department_id' => $departmentId,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all sections for a given department + semester (used by reuseAll).
     */
    public function getSectionsBySemesterId(int $departmentId, int $semesterId): array
    {
        $sql = "
            SELECT section_id, section_name, year_level, max_students
            FROM sections
            WHERE department_id = :department_id
              AND semester_id   = :semester_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':semester_id'   => $semesterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // DUPLICATE CHECKS
    // ──────────────────────────────────────────────

    /**
     * Check if a section name already exists within the SAME semester.
     * This is the correct scope: the same name CAN appear in different semesters
     * of the same academic year (e.g. BSIT-1A in 1st sem AND 2nd sem).
     *
     * @param int|null $excludeSectionId  Pass the current section_id when editing.
     */
    public function existsInSemester(
        int $departmentId,
        string $sectionName,
        int $semesterId,
        ?int $excludeSectionId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM sections
            WHERE department_id = :department_id
              AND section_name  = :section_name
              AND semester_id   = :semester_id
              AND is_active     = 1
        ";
        $params = [
            ':department_id' => $departmentId,
            ':section_name'  => $sectionName,
            ':semester_id'   => $semesterId,
        ];

        if ($excludeSectionId !== null) {
            $sql .= ' AND section_id != :exclude_id';
            $params[':exclude_id'] = $excludeSectionId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ──────────────────────────────────────────────
    // WRITE
    // ──────────────────────────────────────────────

    /**
     * Insert a new section row and return its new ID.
     */
    public function create(
        int $departmentId,
        string $sectionName,
        string $yearLevel,
        int $maxStudents,
        int $currentStudents,
        int $semesterId,
        string $semesterName,
        string $academicYear
    ): int {
        $sql = "
            INSERT INTO sections (
                department_id, section_name, year_level,
                max_students,  current_students,
                semester_id,   semester,        academic_year,
                is_active,     created_at
            ) VALUES (
                :department_id, :section_name, :year_level,
                :max_students,  :current_students,
                :semester_id,   :semester,       :academic_year,
                1,              NOW()
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id'    => $departmentId,
            ':section_name'     => $sectionName,
            ':year_level'       => $yearLevel,
            ':max_students'     => $maxStudents,
            ':current_students' => $currentStudents,
            ':semester_id'      => $semesterId,
            ':semester'         => $semesterName,
            ':academic_year'    => $academicYear,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing section's mutable fields.
     */
    public function update(
        int $sectionId,
        string $sectionName,
        string $yearLevel,
        int $maxStudents,
        int $currentStudents
    ): bool {
        $sql = "
            UPDATE sections
            SET section_name     = :section_name,
                year_level       = :year_level,
                max_students     = :max_students,
                current_students = :current_students,
                updated_at       = NOW()
            WHERE section_id = :section_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':section_name'     => $sectionName,
            ':year_level'       => $yearLevel,
            ':max_students'     => $maxStudents,
            ':current_students' => $currentStudents,
            ':section_id'       => $sectionId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete a section (set is_active = 0).
     */
    public function softDelete(int $sectionId): bool
    {
        $sql = "
            UPDATE sections
            SET is_active  = 0,
                updated_at = NOW()
            WHERE section_id = :section_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':section_id' => $sectionId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Deactivate all sections that are NOT in the given semester
     * (used for auto-transition on semester change).
     */
    public function deactivateOutOfSemester(int $departmentId, int $semesterId): int
    {
        $sql = "
            UPDATE sections
            SET is_active  = 0,
                updated_at = NOW()
            WHERE department_id = :department_id
              AND is_active     = 1
              AND semester_id  != :semester_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':semester_id'   => $semesterId,
        ]);
        return $stmt->rowCount();
    }

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    /**
     * Group a flat section array by year level.
     * Returns ['1st Year' => [...], '2nd Year' => [...], ...]
     */
    public static function groupByYearLevel(array $sections): array
    {
        $grouped = [
            '1st Year' => [],
            '2nd Year' => [],
            '3rd Year' => [],
            '4th Year' => [],
        ];
        foreach ($sections as $section) {
            if (array_key_exists($section['year_level'], $grouped)) {
                $grouped[$section['year_level']][] = $section;
            }
        }
        return $grouped;
    }

    /**
     * Group a flat section array (with semester_name + academic_year columns)
     * into ['1st 2024-2025' => ['1st Year' => [...], ...], ...]
     */
    public static function groupBySemesterAndYear(array $sections): array
    {
        $grouped = [];
        foreach ($sections as $section) {
            $key = $section['semester_name'] . ' ' . $section['academic_year'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    '1st Year' => [],
                    '2nd Year' => [],
                    '3rd Year' => [],
                    '4th Year' => [],
                ];
            }
            if (array_key_exists($section['year_level'], $grouped[$key])) {
                $grouped[$key][$section['year_level']][] = $section;
            }
        }
        return $grouped;
    }
}
