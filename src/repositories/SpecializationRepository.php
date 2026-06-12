<?php

namespace Src\Repositories;

use PDO;
use Exception;

class SpecializationRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Returns true when the faculty already has this course as a specialization.
     */
    public function exists(int $facultyId, int $courseId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM specializations
            WHERE faculty_id = :faculty_id AND course_id = :course_id
        ");
        $stmt->execute([':faculty_id' => $facultyId, ':course_id' => $courseId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Insert a new specialization.
     *
     * $expertiseLevel is optional:
     *   - pass null for Chair / Dean / Faculty (they don't track expertise level)
     *   - pass a string for Director
     *
     * Throws if the specialization already exists.
     */
    public function add(int $facultyId, int $courseId, ?string $expertiseLevel = null): void
    {
        if ($this->exists($facultyId, $courseId)) {
            throw new Exception('You already have this specialization.');
        }

        if ($expertiseLevel !== null) {
            $stmt = $this->db->prepare("
                INSERT INTO specializations (faculty_id, course_id, expertise_level, created_at)
                VALUES (:faculty_id, :course_id, :expertise_level, NOW())
            ");
            $params = [
                ':faculty_id'      => $facultyId,
                ':course_id'       => $courseId,
                ':expertise_level' => $expertiseLevel,
            ];
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO specializations (faculty_id, course_id, created_at)
                VALUES (:faculty_id, :course_id, NOW())
            ");
            $params = [
                ':faculty_id' => $facultyId,
                ':course_id'  => $courseId,
            ];
        }

        if (!$stmt->execute($params)) {
            throw new Exception('Failed to add specialization.');
        }
    }

    /**
     * Delete a specialization row.
     * Throws if not found or if the DELETE affected zero rows.
     */
    public function remove(int $facultyId, int $courseId): void
    {
        if (!$this->exists($facultyId, $courseId)) {
            throw new Exception('Specialization not found for removal.');
        }

        $stmt = $this->db->prepare("
            DELETE FROM specializations
            WHERE faculty_id = :faculty_id AND course_id = :course_id
        ");
        $stmt->execute([':faculty_id' => $facultyId, ':course_id' => $courseId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No specialization was removed. It may have already been deleted.');
        }
    }

    /**
     * Update the expertise_level of an existing specialization.
     * Used only by DirectorController (update_specialization action).
     * Throws if not found or if no row was changed.
     */
    public function update(int $facultyId, int $courseId, string $expertiseLevel): void
    {
        if (!$this->exists($facultyId, $courseId)) {
            throw new Exception('Specialization not found for update.');
        }

        $stmt = $this->db->prepare("
            UPDATE specializations
            SET expertise_level = :expertise_level, updated_at = NOW()
            WHERE faculty_id = :faculty_id AND course_id = :course_id
        ");
        $stmt->execute([
            ':faculty_id'      => $facultyId,
            ':course_id'       => $courseId,
            ':expertise_level' => $expertiseLevel,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No changes were made to the specialization.');
        }
    }
}
