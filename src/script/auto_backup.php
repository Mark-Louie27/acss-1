<?php
require_once __DIR__ . '/../config/database.php';

class AutoBackup {
    private $db;
    private $backupDir;

    public function __construct($db, $backupDir) {
        $this->db = (new Database())->connect();
        $this->backupDir = $backupDir;
    }

    public function createBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFilename = "auto_backup_{$timestamp}.sql";
            $backupPath = $this->backupDir . '/' . $backupFilename;

            // Here you would add the logic to perform the actual database backup.
            // This is a placeholder for demonstration purposes.
            // For example, using exec() to call mysqldump or similar.

            // exec("mysqldump -u username -p password database_name > " . escapeshellarg($backupPath));

            error_log("Automatic backup created: " . $backupFilename);
        } catch (Exception $e) {
            error_log("Automatic backup failed: " . $e->getMessage());
        }
    }
}