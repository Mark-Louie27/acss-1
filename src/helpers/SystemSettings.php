<?php
// Save this as: src/helpers/SystemSettings.php

class SystemSettings
{
    private static $instance = null;
    private $settings = [];
    private $defaults = [
        'system_name' => 'ACSS',
        'system_logo' => '/assets/logo/main_logo/PRMSUlogo.png',
        'primary_color' => '#DA9100',
        'secondary_color' => '#FCC201',
        'background_image' => '/assets/logo/main_logo/campus.jpg',
        'university_name' => 'President Ramon Magsaysay State University',
        'campus_name' => 'Iba Campus',
        'contact_email' => 'info@prmsu.edu.ph',
        'contact_phone' => '+63 (XXX) XXX-XXXX',
        'address' => 'Iba, Zambales, Philippines'
    ];

    private function __construct()
    {
        $this->loadSettings();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSettings()
    {
        try {
            require_once __DIR__ . '/../config/Database.php';
            $db = (new Database())->connect();

            if ($db) {
                $stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings");
                $stmt->execute();
                $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                // Merge with defaults, database values take precedence
                $this->settings = array_merge($this->defaults, $dbSettings);
            } else {
                $this->settings = $this->defaults;
            }
        } catch (PDOException $e) {
            error_log("SystemSettings: Error loading settings - " . $e->getMessage());
            $this->settings = $this->defaults;
        }
    }

    public function get($key, $default = null)
    {
        return $this->settings[$key] ?? $default ?? $this->defaults[$key] ?? null;
    }

    public function getAll()
    {
        return $this->settings;
    }

    public function set($key, $value)
    {
        $this->settings[$key] = $value;
    }

    /**
     * Get logo path with multiple fallback options
     */
    public function getLogoPath($absolute = false)
    {
        $logoSetting = $this->get('system_logo');

        // Possible paths to check
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . $logoSetting,
            __DIR__ . '/../../public' . $logoSetting,
            __DIR__ . '/../..' . $logoSetting,
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $absolute ? $path : $logoSetting;
            }
        }

        // If no file found, return setting path anyway
        error_log("SystemSettings: Logo file not found. Checked paths: " . implode(', ', $possiblePaths));
        return $absolute ? $_SERVER['DOCUMENT_ROOT'] . $logoSetting : $logoSetting;
    }

    /**
     * Get logo as base64 for PDF generation
     */
    public function getLogoBase64()
    {
        $absolutePath = $this->getLogoPath(true);

        if (file_exists($absolutePath)) {
            $imageData = file_get_contents($absolutePath);
            if ($imageData !== false) {
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
        }

        error_log("SystemSettings: Could not load logo from: $absolutePath");
        return ''; // Return empty string if logo not found
    }

    /**
     * Get color with brightness adjustment
     */
    public function getColorVariant($baseColorKey, $brightnessPercent = 0)
    {
        $hex = $this->get($baseColorKey);

        if ($brightnessPercent === 0) {
            return $hex;
        }

        return $this->adjustBrightness($hex, $brightnessPercent);
    }

    private function adjustBrightness($hex, $percent)
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) .
                str_repeat(substr($hex, 1, 1), 2) .
                str_repeat(substr($hex, 2, 1), 2);
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return '#' . str_pad(dechex(round($r)), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex(round($g)), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex(round($b)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Refresh settings from database
     */
    public function refresh()
    {
        $this->loadSettings();
    }
}

// Helper functions for easy access
function getSetting($key, $default = null)
{
    return SystemSettings::getInstance()->get($key, $default);
}

function getAllSettings()
{
    return SystemSettings::getInstance()->getAll();
}

function getLogoPath($absolute = false)
{
    return SystemSettings::getInstance()->getLogoPath($absolute);
}

function getLogoBase64()
{
    return SystemSettings::getInstance()->getLogoBase64();
}

function getColorVariant($baseColorKey, $brightnessPercent = 0)
{
    return SystemSettings::getInstance()->getColorVariant($baseColorKey, $brightnessPercent);
}
