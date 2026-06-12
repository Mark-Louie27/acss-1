<?php

namespace Src\Repositories;

use PDO;
use Exception;

class CourseRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Search courses by code, name, department, or college.
     *
     * Behaviour by query length:
     *   ''       → all courses, ordered by code            (LIMIT 200)
     *   1 char   → prefix match on code / name             (LIMIT 100)
     *   2+ chars → LIKE match on code, name, dept, college (LIMIT 100), relevance-ranked
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        // -------------------------------------------------------------------
        // Empty → return full list (e.g. for populating a dropdown on focus)
        // -------------------------------------------------------------------
        if ($query === '') {
            $stmt = $this->db->prepare("
                SELECT c.course_id, c.course_code, c.course_name,
                       d.department_name, co.college_name
                FROM courses c
                JOIN departments d ON c.department_id = d.department_id
                JOIN colleges co   ON d.college_id    = co.college_id
                ORDER BY c.course_code
                LIMIT 200
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $upper = strtoupper($query);

        // -------------------------------------------------------------------
        // Single character → prefix search only (no dept/college)
        // -------------------------------------------------------------------
        if (strlen($query) === 1) {
            $prefix = $upper . '%';
            $stmt   = $this->db->prepare("
                SELECT c.course_id, c.course_code, c.course_name,
                       d.department_name, co.college_name
                FROM courses c
                JOIN departments d ON c.department_id = d.department_id
                JOIN colleges co   ON d.college_id    = co.college_id
                WHERE UPPER(c.course_code) LIKE ?
                   OR UPPER(c.course_name) LIKE ?
                ORDER BY
                    CASE
                        WHEN UPPER(c.course_code) LIKE ? THEN 1
                        WHEN UPPER(c.course_name) LIKE ? THEN 2
                        ELSE 3
                    END,
                    c.course_code
                LIMIT 100
            ");
            $stmt->execute([$prefix, $prefix, $prefix, $prefix]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // -------------------------------------------------------------------
        // 2+ characters → full search: code, name, department, college
        // -------------------------------------------------------------------
        $like = '%' . $upper . '%';
        $stmt = $this->db->prepare("
            SELECT c.course_id, c.course_code, c.course_name,
                   d.department_name, co.college_name
            FROM courses c
            JOIN departments d ON c.department_id = d.department_id
            JOIN colleges co   ON d.college_id    = co.college_id
            WHERE UPPER(c.course_code)     LIKE ?
               OR UPPER(c.course_name)     LIKE ?
               OR UPPER(d.department_name) LIKE ?
               OR UPPER(co.college_name)   LIKE ?
            ORDER BY
                CASE
                    WHEN UPPER(c.course_code)     LIKE ? THEN 1
                    WHEN UPPER(c.course_name)     LIKE ? THEN 2
                    WHEN UPPER(d.department_name) LIKE ? THEN 3
                    WHEN UPPER(co.college_name)   LIKE ? THEN 4
                    ELSE 5
                END,
                c.course_code
            LIMIT 100
        ");
        // 8 positional params: 4 for WHERE, 4 for ORDER BY CASE
        $stmt->execute(array_fill(0, 8, $like));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
