<?php

namespace Src\Repositories;

use PDO;
use Exception;

class ProfileRepository
{
    public function __construct(private PDO $db) {}

    // =========================================================================
    // USER
    // =========================================================================

    /**
     * Basic user row with department, college, role, and faculty joins.
     * Used for the initial lightweight fetch before stats are needed.
     */
    public function getUserWithDetails(int $userId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT u.*, d.department_name, c.college_name, r.role_name,
                   f.academic_rank, f.employment_type, f.classification,
                   f.bachelor_degree, f.master_degree, f.doctorate_degree,
                   f.post_doctorate_degree, f.advisory_class, f.designation
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c    ON u.college_id    = c.college_id
            LEFT JOIN roles r       ON u.role_id       = r.role_id
            LEFT JOIN faculty f     ON u.user_id       = f.user_id
            WHERE u.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Full user row + computed stat columns for the profile page dashboard.
     * Replaces the duplicate second SELECT that existed in every controller.
     */
    public function getUserWithStats(int $userId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT u.*, d.department_name, c.college_name, r.role_name,
                   f.academic_rank, f.employment_type, f.classification,
                   f.bachelor_degree, f.master_degree, f.doctorate_degree,
                   f.post_doctorate_degree, f.advisory_class, f.designation,

                   (SELECT COUNT(*)
                      FROM faculty f2
                      JOIN users fu ON f2.user_id = fu.user_id
                     WHERE fu.department_id = u.department_id)           AS facultyCount,

                   (SELECT COUNT(DISTINCT sch.course_id)
                      FROM schedules sch
                     WHERE sch.faculty_id = f.faculty_id)                AS coursesCount,

                   (SELECT COUNT(*)
                      FROM specializations s2
                     WHERE s2.faculty_id = f.faculty_id)                 AS specializationsCount,

                   (SELECT COUNT(*)
                      FROM faculty_requests fr
                     WHERE fr.department_id = u.department_id
                       AND fr.status = 'pending')                        AS pendingApplicantsCount,

                   (SELECT semester_name
                      FROM semesters
                     WHERE is_current = 1
                     LIMIT 1)                                            AS currentSemester,

                   (SELECT created_at
                      FROM auth_logs
                     WHERE user_id = u.user_id
                       AND action  = 'login_success'
                     ORDER BY created_at DESC
                     LIMIT 1)                                            AS lastLogin

            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c    ON u.college_id    = c.college_id
            LEFT JOIN roles r       ON u.role_id       = r.role_id
            LEFT JOIN faculty f     ON u.user_id       = f.user_id
            WHERE u.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Dynamically UPDATE the users table.
     * Only whitelisted columns are ever written; empty strings are skipped.
     */
    public function updateUser(int $userId, array $fields): void
    {
        $allowed = [
            'email',
            'phone',
            'username',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'title',
            'profile_picture',
        ];

        $setClause = [];
        $params    = [':user_id' => $userId];

        foreach ($allowed as $col) {
            if (isset($fields[$col]) && $fields[$col] !== '') {
                $setClause[]     = "`$col` = :$col";
                $params[":$col"] = $fields[$col];
            }
        }

        if (empty($setClause)) {
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE users SET ' . implode(', ', $setClause) . ', updated_at = NOW() WHERE user_id = :user_id'
        );

        if (!$stmt->execute($params)) {
            throw new Exception('Failed to update user profile.');
        }
    }

    // =========================================================================
    // FACULTY
    // =========================================================================

    /** Returns the faculty_id for a user, or false if not found. */
    public function getFacultyId(int $userId): int|false
    {
        $stmt = $this->db->prepare('SELECT faculty_id FROM faculty WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : false;
    }

    /**
     * Dynamically UPDATE the faculty table.
     * Only whitelisted columns are ever written; empty strings are skipped.
     */
    public function updateFaculty(int $facultyId, array $fields): void
    {
        $allowed = [
            'academic_rank',
            'employment_type',
            'classification',
            'designation',
            'advisory_class',
            'bachelor_degree',
            'master_degree',
            'doctorate_degree',
            'post_doctorate_degree',
        ];

        $setClause = [];
        $params    = [':faculty_id' => $facultyId];

        foreach ($allowed as $col) {
            if (isset($fields[$col]) && $fields[$col] !== '') {
                $setClause[]     = "$col = :$col";
                $params[":$col"] = $fields[$col];
            }
        }

        if (empty($setClause)) {
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE faculty SET ' . implode(', ', $setClause) . ', updated_at = NOW() WHERE faculty_id = :faculty_id'
        );

        if (!$stmt->execute($params)) {
            throw new Exception('Failed to update faculty information.');
        }
    }

    // =========================================================================
    // SPECIALIZATIONS
    // =========================================================================

    /** All specializations for a user, resolved via faculty subquery. */
    public function getSpecializations(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.expertise_level AS level, c.course_code, c.course_name, s.course_id
            FROM specializations s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = :user_id)
            ORDER BY c.course_code
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
