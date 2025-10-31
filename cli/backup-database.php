<?php

/**
 * CLI Script for Automated Database Backup
 * Usage: php cli/backup-database.php
 * Place in: cli/backup-database.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine base path
$basePath = dirname(__DIR__);

// Load environment variables first (before any class loading)
$envFile = $basePath . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

class DatabaseBackupCLI
{
    private $db;
    private $logFile;
    private $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        try {
            // Get database credentials from environment
            $dbConfig = $this->getDatabaseConfig();

            // Create PDO connection directly
            $this->db = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                $dbConfig['user'],
                $dbConfig['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Set log file path
            $logDir = $this->basePath . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $this->logFile = $logDir . '/backup-' . date('Y-m') . '.log';
        } catch (Exception $e) {
            $this->log("ERROR: Failed to initialize: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Get database configuration from environment variables
     */
    private function getDatabaseConfig()
    {
        // Check if environment variables are set
        if (
            !isset($_ENV['DB_HOST']) || !isset($_ENV['DB_NAME']) ||
            !isset($_ENV['DB_USER']) || !isset($_ENV['DB_PASS'])
        ) {
            throw new Exception("Database configuration missing in environment variables");
        }

        return [
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'pass' => $_ENV['DB_PASS']
        ];
    }

    public function run()
    {
        $this->log("========================================");
        $this->log("Starting automated database backup...");
        $this->log("========================================");

        try {
            $result = $this->createDatabaseBackup();

            if ($result['success']) {
                $this->log("✓ SUCCESS: " . $result['message']);
                exit(0);
            } else {
                $this->log("✗ ERROR: " . $result['message']);
                exit(1);
            }
        } catch (Exception $e) {
            $this->log("✗ FATAL ERROR: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            exit(1);
        }
    }

    private function createDatabaseBackup()
    {
        try {
            // Create backup directory if it doesn't exist
            $backupDir = $this->basePath . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Get database name
            $stmt = $this->db->query("SELECT DATABASE()");
            $dbName = $stmt->fetchColumn();

            $this->log("Database: {$dbName}");

            // Generate backup filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $backupFilename = "backup_{$dbName}_{$timestamp}.sql";
            $backupPath = $backupDir . '/' . $backupFilename;

            // Get all tables
            $stmt = $this->db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $this->log("Tables to backup: " . count($tables));

            $backupContent = "";

            // Set SQL headers
            $backupContent .= "-- Database Backup\n";
            $backupContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $backupContent .= "-- Database: {$dbName}\n";
            $backupContent .= "-- PHP Version: " . PHP_VERSION . "\n";
            $backupContent .= "-- Tables: " . count($tables) . "\n";
            $backupContent .= "\nSET FOREIGN_KEY_CHECKS=0;\n";
            $backupContent .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
            $backupContent .= "SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;\n";
            $backupContent .= "SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;\n";
            $backupContent .= "SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;\n";
            $backupContent .= "SET NAMES utf8mb4;\n\n";

            // Backup each table
            foreach ($tables as $index => $table) {
                $this->log("Backing up table " . ($index + 1) . "/" . count($tables) . ": {$table}");
                $backupContent .= $this->backupTable($table);
            }

            // Restore settings
            $backupContent .= "SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;\n";
            $backupContent .= "SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;\n";
            $backupContent .= "SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;\n";
            $backupContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Write backup file
            $this->log("Writing backup file...");
            if (file_put_contents($backupPath, $backupContent) !== false) {
                $fileSize = filesize($backupPath);
                $this->log("Backup file created: " . $this->formatBytes($fileSize));

                // Compress the backup
                $this->log("Compressing backup...");
                $compressed = $this->compressBackup($backupPath);

                // Set file permissions
                if ($compressed && file_exists($backupPath . '.zip')) {
                    chmod($backupPath . '.zip', 0644);
                    $finalSize = filesize($backupPath . '.zip');
                    $this->log("Backup compressed: " . $this->formatBytes($finalSize));
                } else {
                    chmod($backupPath, 0644);
                }

                // Clean up old backups (keep last 30 days)
                $this->log("Cleaning up old backups...");
                $this->cleanupOldBackups();

                $finalFilename = $compressed ? $backupFilename . '.zip' : $backupFilename;
                $finalPath = $compressed ? $backupPath . '.zip' : $backupPath;
                $finalFileSize = $this->formatBytes(filesize($finalPath));

                return [
                    'success' => true,
                    'message' => "Database backup created successfully: {$finalFilename} ({$finalFileSize})",
                    'filename' => $finalFilename
                ];
            } else {
                throw new Exception('Failed to write backup file');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create backup: ' . $e->getMessage()];
        }
    }

    private function backupTable($tableName)
    {
        $output = "--\n";
        $output .= "-- Table structure for table `{$tableName}`\n";
        $output .= "--\n\n";

        // Drop table if exists
        $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

        // Get table creation script
        $stmt = $this->db->query("SHOW CREATE TABLE `{$tableName}`");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $output .= $createTable['Create Table'] . ";\n\n";

        // Get table data
        $output .= "--\n";
        $output .= "-- Dumping data for table `{$tableName}`\n";
        $output .= "--\n\n";

        $stmt = $this->db->query("SELECT * FROM `{$tableName}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            // Get column names
            $columns = array_keys($rows[0]);
            $columnNames = implode('`, `', $columns);

            $output .= "INSERT INTO `{$tableName}` (`{$columnNames}`) VALUES \n";

            $insertValues = [];
            foreach ($rows as $row) {
                $values = array_map(function ($value) {
                    if ($value === null) return 'NULL';
                    // Escape special characters properly
                    $value = addslashes($value);
                    return "'" . $value . "'";
                }, $row);

                $insertValues[] = "(" . implode(", ", $values) . ")";
            }

            $output .= implode(",\n", $insertValues) . ";\n\n";
        }

        return $output;
    }

    private function compressBackup($backupPath)
    {
        if (!extension_loaded('zip')) {
            $this->log("ZIP extension not loaded, skipping compression");
            return false;
        }

        try {
            $zip = new ZipArchive();
            $zipPath = $backupPath . '.zip';

            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($backupPath, basename($backupPath));
                $zip->close();

                // Remove the original SQL file if zip was created successfully
                if (file_exists($zipPath)) {
                    unlink($backupPath);
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->log("Compression failed: " . $e->getMessage());
        }

        return false;
    }

    private function cleanupOldBackups($keepDays = 30)
    {
        try {
            $backupDir = $this->basePath . '/backups';
            if (!is_dir($backupDir)) {
                return;
            }

            $files = scandir($backupDir);
            $cutoffTime = time() - ($keepDays * 24 * 60 * 60);
            $deletedCount = 0;

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $filePath = $backupDir . '/' . $file;
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                if (($ext === 'sql' || $ext === 'zip') && is_file($filePath)) {
                    if (filemtime($filePath) < $cutoffTime) {
                        unlink($filePath);
                        $this->log("Deleted old backup: {$file}");
                        $deletedCount++;
                    }
                }
            }

            if ($deletedCount > 0) {
                $this->log("Cleaned up {$deletedCount} old backup(s)");
            } else {
                $this->log("No old backups to clean up");
            }
        } catch (Exception $e) {
            $this->log("Cleanup error: " . $e->getMessage());
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";

        // Write to log file
        @file_put_contents($this->logFile, $logMessage, FILE_APPEND);

        // Also output to console
        echo $logMessage;
    }
}

// Run the backup
$backup = new DatabaseBackupCLI($basePath);
$backup->run();
