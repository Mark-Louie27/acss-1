<?php

namespace App\Services;

/**
 * Backup Scheduler Service
 * Handles automated daily database backups
 * Place in: src/Services/BackupSchedulerService.php
 */
class BackupSchedulerService
{
    private $basePath;
    private $schedulerFile;
    private $lockFile;

    public function __construct($basePath = null)
    {
        // Auto-detect base path if not provided
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
        $this->schedulerFile = $this->basePath . '/storage/scheduler.json';
        $this->lockFile = $this->basePath . '/storage/backup.lock';

        // Ensure directories exist
        $this->ensureDirectoriesExist();
    }

    /**
     * Main method to check and run backup
     * Call this from your bootstrap/index.php
     */
    public function checkAndRunBackup()
    {
        // Don't run on CLI requests
        if (php_sapi_name() === 'cli') {
            return;
        }

        // Check if another backup is running
        if ($this->isBackupRunning()) {
            return;
        }

        // Check if backup is due
        if (!$this->isBackupDue()) {
            return;
        }

        // Run backup in background (non-blocking)
        $this->runBackupInBackground();
    }

    /**
     * Check if backup is currently running
     */
    public function isBackupRunning()
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }

        // Check if lock file is stale (older than 1 hour)
        $lockTime = filemtime($this->lockFile);
        if (time() - $lockTime > 3600) {
            @unlink($this->lockFile);
            return false;
        }

        return true;
    }

    /**
     * Check if backup is due (once per day at scheduled time)
     */
    private function isBackupDue()
    {
        $schedulerData = $this->getSchedulerData();

        // Get last backup time
        $lastBackup = $schedulerData['last_backup'] ?? 0;

        // Check if 24 hours have passed
        $hoursSinceLastBackup = (time() - $lastBackup) / 3600;

        // Get scheduled hour (default 2 AM)
        $currentHour = (int)date('G');
        $scheduledHour = $schedulerData['scheduled_hour'] ?? 2;

        // Run if 24 hours passed AND current hour is within scheduled window
        return $hoursSinceLastBackup >= 23 &&
            ($currentHour >= $scheduledHour && $currentHour < $scheduledHour + 2);
    }

    /**
     * Run backup in background (non-blocking)
     */
    private function runBackupInBackground()
    {
        try {
            // Create lock file
            $this->createLockFile();

            // Update scheduler data
            $this->updateSchedulerData(['last_backup' => time()]);

            // Get CLI script path
            $cliScript = $this->basePath . '/cli/backup-database.php';
            $logFile = $this->basePath . '/logs/scheduler-backup.log';

            // Check if CLI script exists
            if (!file_exists($cliScript)) {
                error_log("Backup CLI script not found: {$cliScript}");
                $this->removeLockFile();
                return;
            }

            // Run backup in background based on OS
            if ($this->isWindows()) {
                // Windows
                $phpPath = defined('PHP_BINARY') ? PHP_BINARY : 'php';
                $command = "start /B \"\" \"{$phpPath}\" \"{$cliScript}\" > \"{$logFile}\" 2>&1";
                pclose(popen($command, 'r'));
            } else {
                // Linux/Unix
                $phpPath = defined('PHP_BINARY') ? PHP_BINARY : 'php';
                $command = "{$phpPath} {$cliScript} > {$logFile} 2>&1 &";
                exec($command);
            }

            error_log("Backup scheduled in background at " . date('Y-m-d H:i:s'));
        } catch (\Exception $e) {
            error_log("Failed to schedule backup: " . $e->getMessage());
            $this->removeLockFile();
        }
    }

    /**
     * Create lock file
     */
    private function createLockFile()
    {
        $lockDir = dirname($this->lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        file_put_contents($this->lockFile, json_encode([
            'time' => time(),
            'pid' => getmypid(),
            'date' => date('Y-m-d H:i:s')
        ]));
    }

    /**
     * Remove lock file
     */
    public function removeLockFile()
    {
        if (file_exists($this->lockFile)) {
            @unlink($this->lockFile);
        }
    }

    /**
     * Get scheduler data
     */
    private function getSchedulerData()
    {
        if (!file_exists($this->schedulerFile)) {
            return [
                'last_backup' => 0,
                'scheduled_hour' => 2, // 2 AM default
                'next_backup' => null,
                'total_backups' => 0
            ];
        }

        $data = json_decode(file_get_contents($this->schedulerFile), true);
        return $data ?? [];
    }

    /**
     * Update scheduler data
     */
    private function updateSchedulerData($data)
    {
        $currentData = $this->getSchedulerData();
        $newData = array_merge($currentData, $data);

        // Increment backup count if this is a new backup
        if (isset($data['last_backup'])) {
            $newData['total_backups'] = ($currentData['total_backups'] ?? 0) + 1;
        }

        // Calculate next backup time
        $scheduledHour = $newData['scheduled_hour'] ?? 2;
        $nextBackupTimestamp = strtotime("tomorrow {$scheduledHour}:00:00");
        $newData['next_backup'] = date('Y-m-d H:i:s', $nextBackupTimestamp);

        file_put_contents($this->schedulerFile, json_encode($newData, JSON_PRETTY_PRINT));
    }

    /**
     * Get next scheduled backup time
     */
    public function getNextBackupTime()
    {
        $data = $this->getSchedulerData();
        return $data['next_backup'] ?? 'Not scheduled';
    }

    /**
     * Get last backup time
     */
    public function getLastBackupTime()
    {
        $data = $this->getSchedulerData();
        $lastBackup = $data['last_backup'] ?? 0;
        return $lastBackup > 0 ? date('Y-m-d H:i:s', $lastBackup) : 'Never';
    }

    /**
     * Get total backups created
     */
    public function getTotalBackupsCreated()
    {
        $data = $this->getSchedulerData();
        return $data['total_backups'] ?? 0;
    }

    /**
     * Get scheduler status
     */
    public function getSchedulerStatus()
    {
        $data = $this->getSchedulerData();
        $lastBackup = $data['last_backup'] ?? 0;
        $hoursSinceLastBackup = $lastBackup > 0 ? (time() - $lastBackup) / 3600 : null;

        return [
            'is_running' => $this->isBackupRunning(),
            'scheduled_hour' => $data['scheduled_hour'] ?? 2,
            'last_backup' => $lastBackup > 0 ? date('Y-m-d H:i:s', $lastBackup) : 'Never',
            'next_backup' => $data['next_backup'] ?? 'Not scheduled',
            'hours_since_last_backup' => $hoursSinceLastBackup ? round($hoursSinceLastBackup, 1) : null,
            'total_backups' => $data['total_backups'] ?? 0,
            'is_backup_due' => $this->isBackupDue()
        ];
    }

    /**
     * Manually trigger backup
     */
    public function triggerManualBackup()
    {
        if ($this->isBackupRunning()) {
            return ['success' => false, 'message' => 'Backup is already running'];
        }

        $this->runBackupInBackground();
        return ['success' => true, 'message' => 'Backup started in background'];
    }

    /**
     * Update scheduled hour (0-23)
     */
    public function setScheduledHour($hour)
    {
        if ($hour < 0 || $hour > 23) {
            return false;
        }

        $this->updateSchedulerData(['scheduled_hour' => (int)$hour]);
        return true;
    }

    /**
     * Check if running on Windows
     */
    private function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist()
    {
        $directories = [
            dirname($this->schedulerFile), // storage
            $this->basePath . '/logs',
            $this->basePath . '/backups'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Get scheduler statistics
     */
    public function getStatistics()
    {
        $backupDir = $this->basePath . '/backups';
        $totalSize = 0;
        $backupCount = 0;

        if (is_dir($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $filePath = $backupDir . '/' . $file;
                if (is_file($filePath) && (pathinfo($file, PATHINFO_EXTENSION) === 'sql' || pathinfo($file, PATHINFO_EXTENSION) === 'zip')) {
                    $totalSize += filesize($filePath);
                    $backupCount++;
                }
            }
        }

        return [
            'total_backups_on_disk' => $backupCount,
            'total_size' => $this->formatBytes($totalSize),
            'total_size_bytes' => $totalSize
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
