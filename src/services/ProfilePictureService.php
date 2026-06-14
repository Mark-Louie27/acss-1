<?php

namespace Src\Services;

use PDO;
use Exception;

/**
 * ProfilePictureService
 *
 * Handles profile picture uploads for any role controller.
 * Drop this into src/services/ alongside AuthService, EmailService, etc.
 *
 * Upload path matches the existing src/public/uploads/profiles/ folder.
 */
class ProfilePictureService
{
    private const UPLOAD_WEB_PATH = '/uploads/profiles/';
    private const ALLOWED_TYPES   = ['image/jpeg', 'image/png', 'image/gif'];
    private const MAX_SIZE        = 2 * 1024 * 1024; // 2 MB

    public function __construct(private PDO $db) {}

    /**
     * Process a profile picture upload for $userId.
     *
     * Returns:
     *   null   — no file was submitted (UPLOAD_ERR_NO_FILE)
     *   string — web-relative path, e.g. "/uploads/profiles/profile_5_1718000000.jpg"
     *
     * Throws \Exception on any validation or I/O failure.
     */
    public function upload(int $userId): ?string
    {
        if (
            !isset($_FILES['profile_picture']) ||
            $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        $file = $_FILES['profile_picture'];

        $this->assertNoUploadError($file['error']);
        $this->assertAllowedType($file['type'], $userId);
        $this->assertSize($file['size'], $userId);

        $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename   = "profile_{$userId}_" . time() . ".{$ext}";
        $uploadDir  = $_SERVER['DOCUMENT_ROOT'] . self::UPLOAD_WEB_PATH;
        $uploadPath = $uploadDir . $filename;

        $this->ensureDirectory($uploadDir);
        $this->deleteExistingPicture($userId);

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log("ProfilePictureService: move_uploaded_file failed for user $userId → $uploadPath");
            throw new Exception('Failed to save the uploaded file. Check server permissions or disk space.');
        }

        error_log("ProfilePictureService: saved profile picture for user $userId → $uploadPath");
        return self::UPLOAD_WEB_PATH . $filename;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function assertNoUploadError(int $code): void
    {
        if ($code === UPLOAD_ERR_OK) return;

        $messages = [
            UPLOAD_ERR_INI_SIZE   => "The file exceeds the server's maximum upload size.",
            UPLOAD_ERR_FORM_SIZE  => "The file exceeds the form's maximum upload size.",
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'The server failed to write the file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A server extension stopped the upload.',
        ];

        throw new Exception($messages[$code] ?? "Upload failed with error code $code.");
    }

    private function assertAllowedType(string $mime, int $userId): void
    {
        if (in_array($mime, self::ALLOWED_TYPES, true)) return;
        error_log("ProfilePictureService: invalid MIME '$mime' for user $userId");
        throw new Exception('Only JPEG, PNG, and GIF images are allowed.');
    }

    private function assertSize(int $bytes, int $userId): void
    {
        if ($bytes <= self::MAX_SIZE) return;
        error_log("ProfilePictureService: file size $bytes exceeds limit for user $userId");
        throw new Exception('The file exceeds the 2 MB size limit.');
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            error_log("ProfilePictureService: could not create $dir");
            throw new Exception('Could not create the upload directory on the server.');
        }
    }

    /** Delete the user's current profile picture from disk (non-fatal if missing). */
    private function deleteExistingPicture(int $userId): void
    {
        $stmt = $this->db->prepare('SELECT profile_picture FROM users WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $webPath = $stmt->fetchColumn();

        if (!$webPath) return;

        $full = $_SERVER['DOCUMENT_ROOT'] . $webPath;
        if (file_exists($full) && !unlink($full)) {
            // Non-fatal — log and continue so the new upload still proceeds
            error_log("ProfilePictureService: could not delete $full for user $userId");
        }
    }
}
