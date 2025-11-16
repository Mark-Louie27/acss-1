<?php
// Place this file in your public folder and access via browser
// IMPORTANT: Remove this file after debugging!

require_once __DIR__ . '/../../config/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <title>ACSS Database Diagnostic</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        .warning {
            color: orange;
            font-weight: bold;
        }

        .section {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        pre {
            background: #eee;
            padding: 10px;
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #4CAF50;
            color: white;
        }
    </style>
</head>

<body>
    <h1>🔍 ACSS Database Diagnostic Tool</h1>
    <p><strong>⚠️ SECURITY WARNING: Delete this file after debugging!</strong></p>

    <?php
    try {
        $db = (new Database())->connect();
        echo "<div class='section'><span class='success'>✓ Database connection successful</span></div>";

        // Check 1: Check if schedules table exists
        echo "<div class='section'><h2>1. Checking Schedules Table Structure</h2>";
        $stmt = $db->query("DESCRIBE schedules");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $requiredColumns = ['is_public', 'approval_status_director', 'approval_status_dean'];
        $missingColumns = [];
        $existingColumns = array_column($columns, 'Field');

        foreach ($requiredColumns as $col) {
            if (!in_array($col, $existingColumns)) {
                $missingColumns[] = $col;
            }
        }

        if (empty($missingColumns)) {
            echo "<span class='success'>✓ All required columns exist</span>";
        } else {
            echo "<span class='error'>✗ Missing columns: " . implode(', ', $missingColumns) . "</span>";
            echo "<h3>Run this SQL to fix:</h3><pre>";
            foreach ($missingColumns as $col) {
                if ($col === 'is_public') {
                    echo "ALTER TABLE schedules ADD COLUMN is_public TINYINT(1) DEFAULT 0;\n";
                } elseif ($col === 'approval_status_director') {
                    echo "ALTER TABLE schedules ADD COLUMN approval_status_director VARCHAR(20) DEFAULT 'pending';\n";
                } elseif ($col === 'approval_status_dean') {
                    echo "ALTER TABLE schedules ADD COLUMN approval_status_dean VARCHAR(20) DEFAULT 'pending';\n";
                }
            }
            echo "</pre>";
        }

        echo "<h3>Current Table Structure:</h3>";
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table></div>";

        // Check 2: Count schedules by approval status
        echo "<div class='section'><h2>2. Schedule Approval Status</h2>";

        $stmt = $db->query("
            SELECT 
                COALESCE(approval_status_director, 'NULL') as director_status,
                COALESCE(approval_status_dean, 'NULL') as dean_status,
                COALESCE(is_public, 'N/A') as is_public,
                COUNT(*) as count
            FROM schedules
            GROUP BY approval_status_director, approval_status_dean, is_public
        ");
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($stats)) {
            echo "<span class='warning'>⚠ No schedules found in database</span>";
        } else {
            echo "<table>";
            echo "<tr><th>Director Status</th><th>Dean Status</th><th>Is Public</th><th>Count</th></tr>";
            foreach ($stats as $row) {
                echo "<tr>";
                echo "<td>{$row['director_status']}</td>";
                echo "<td>{$row['dean_status']}</td>";
                echo "<td>{$row['is_public']}</td>";
                echo "<td><strong>{$row['count']}</strong></td>";
                echo "</tr>";
            }
            echo "</table>";

            // Count approved schedules
            $stmt = $db->query("
                SELECT COUNT(*) as approved_count
                FROM schedules
                WHERE approval_status_director = 'approved' 
                   OR approval_status_dean = 'approved'
            ");
            $approvedCount = $stmt->fetch(PDO::FETCH_ASSOC)['approved_count'];

            if ($approvedCount > 0) {
                echo "<p><span class='success'>✓ Found {$approvedCount} approved schedules</span></p>";
            } else {
                echo "<p><span class='error'>✗ No approved schedules found!</span></p>";
                echo "<p>Schedules need to be approved by Director or Dean to appear publicly.</p>";
            }
        }
        echo "</div>";

        // Check 3: Check current semester
        echo "<div class='section'><h2>3. Current Semester Check</h2>";
        $stmt = $db->query("SELECT * FROM semesters WHERE is_current = 1 LIMIT 1");
        $semester = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($semester) {
            echo "<span class='success'>✓ Current semester found:</span>";
            echo "<pre>" . print_r($semester, true) . "</pre>";

            // Count schedules in current semester
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM schedules
                WHERE semester_id = ?
                  AND (approval_status_director = 'approved' OR approval_status_dean = 'approved')
            ");
            $stmt->execute([$semester['semester_id']]);
            $semesterSchedules = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            echo "<p>Approved schedules in current semester: <strong>{$semesterSchedules}</strong></p>";
        } else {
            echo "<span class='error'>✗ No current semester found!</span>";
            echo "<p>Set a semester as current by running:</p>";
            echo "<pre>UPDATE semesters SET is_current = 1 WHERE semester_id = YOUR_SEMESTER_ID;</pre>";
        }
        echo "</div>";

        // Check 4: Sample approved schedules
        echo "<div class='section'><h2>4. Sample Approved Schedules (First 5)</h2>";
        $stmt = $db->query("
            SELECT 
                s.schedule_id,
                c.course_code,
                c.course_name,
                sec.section_name,
                s.approval_status_director,
                s.approval_status_dean,
                s.is_public,
                sem.semester_name
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            JOIN sections sec ON s.section_id = sec.section_id
            JOIN semesters sem ON s.semester_id = sem.semester_id
            WHERE (s.approval_status_director = 'approved' OR s.approval_status_dean = 'approved')
            LIMIT 5
        ");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($samples)) {
            echo "<span class='warning'>⚠ No approved schedules to display</span>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>Course</th><th>Section</th><th>Director</th><th>Dean</th><th>Public</th><th>Semester</th></tr>";
            foreach ($samples as $row) {
                echo "<tr>";
                echo "<td>{$row['schedule_id']}</td>";
                echo "<td>{$row['course_code']} - {$row['course_name']}</td>";
                echo "<td>{$row['section_name']}</td>";
                echo "<td>{$row['approval_status_director']}</td>";
                echo "<td>{$row['approval_status_dean']}</td>";
                echo "<td>" . ($row['is_public'] ?? 'N/A') . "</td>";
                echo "<td>{$row['semester_name']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";

        // Check 5: Logo file check
        echo "<div class='section'><h2>5. Logo File Check</h2>";
        $logoPaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/assets/logo/main_logo/PRMSUlogo.png',
            __DIR__ . '/../public/assets/logo/main_logo/PRMSUlogo.png',
            __DIR__ . '/../../public/assets/logo/main_logo/PRMSUlogo.png'
        ];

        $logoFound = false;
        foreach ($logoPaths as $path) {
            if (file_exists($path)) {
                echo "<span class='success'>✓ Logo found at: $path</span><br>";
                $logoFound = true;
                break;
            } else {
                echo "<span class='warning'>⚠ Not found: $path</span><br>";
            }
        }

        if (!$logoFound) {
            echo "<p><span class='error'>✗ Logo file not found in any expected location</span></p>";
            echo "<p>Upload PRMSUlogo.png to one of the paths above</p>";
        }
        echo "</div>";

        // Check 6: PHP Configuration
        echo "<div class='section'><h2>6. PHP Configuration</h2>";
        echo "<table>";
        echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
        echo "<tr><td>Max Execution Time</td><td>" . ini_get('max_execution_time') . " seconds</td></tr>";
        echo "<tr><td>Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
        echo "<tr><td>Upload Max Filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
        echo "<tr><td>Post Max Size</td><td>" . ini_get('post_max_size') . "</td></tr>";
        echo "<tr><td>Display Errors</td><td>" . (ini_get('display_errors') ? 'On' : 'Off') . "</td></tr>";
        echo "</table></div>";
    } catch (PDOException $e) {
        echo "<div class='section'><span class='error'>✗ Database Error: " . $e->getMessage() . "</span></div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } catch (Exception $e) {
        echo "<div class='section'><span class='error'>✗ Error: " . $e->getMessage() . "</span></div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    ?>

    <div class='section'>
        <h2>✅ Quick Fixes</h2>
        <h3>If schedules aren't showing:</h3>
        <ol>
            <li>Make sure schedules are approved (approval_status_director = 'approved' OR approval_status_dean = 'approved')</li>
            <li>Verify there's a current semester (is_current = 1 in semesters table)</li>
            <li>Check that schedules belong to the current semester</li>
        </ol>

        <h3>To approve all schedules for testing:</h3>
        <pre>UPDATE schedules SET approval_status_director = 'approved', approval_status_dean = 'approved' WHERE semester_id = YOUR_CURRENT_SEMESTER_ID;</pre>

        <h3>If PDF download fails:</h3>
        <ol>
            <li>Check PHP memory_limit (should be at least 256M)</li>
            <li>Verify logo file exists in correct path</li>
            <li>Check error logs for specific PDF library errors</li>
            <li>Ensure PdfService class is properly configured</li>
        </ol>
    </div>

    <div class='section' style='background: #ffebee;'>
        <h2>⚠️ IMPORTANT SECURITY NOTE</h2>
        <p><strong>DELETE THIS FILE (diagnostic.php) AFTER DEBUGGING!</strong></p>
        <p>This file exposes database structure and should not be accessible in production.</p>
    </div>
</body>

</html>