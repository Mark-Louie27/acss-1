<?php

namespace Src\Repositories;

use PDO;
use Exception;

class SpecializationRepository
{
    public function __construct(private PDO $db) {}

    public function exists(int $facultyId, int $courseId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM specializations
            WHERE faculty_id = :faculty_id AND course_id = :course_id
        ");
        $stmt->execute([':faculty_id' => $facultyId, ':course_id' => $courseId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Insert a new specialization.
     * $expertiseLevel = null  → Chair / Dean / Faculty (column not written)
     * $expertiseLevel = string → Director
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
            $stmt->execute([
                ':faculty_id'      => $facultyId,
                ':course_id'       => $courseId,
                ':expertise_level' => $expertiseLevel,
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO specializations (faculty_id, course_id, created_at)
                VALUES (:faculty_id, :course_id, NOW())
            ");
            $stmt->execute([':faculty_id' => $facultyId, ':course_id' => $courseId]);
        }
    }

    /** Delete a specialization. Throws if not found or zero rows affected. */
    public function remove(int $facultyId, int $courseId): void
    {
        if (!$this->exists($facultyId, $courseId)) {
            throw new Exception('Specialization not found for removal.');
        }

        $stmt = $this->db->prepare("
            DELETE FROM specializations WHERE faculty_id = :faculty_id AND course_id = :course_id
        ");
        $stmt->execute([':faculty_id' => $facultyId, ':course_id' => $courseId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No specialization was removed. It may have already been deleted.');
        }
    }

    /** Update expertise_level on an existing specialization. Director only. */
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
