<?php
require_once __DIR__ . '/../services/SchedulingService.php';
require_once __DIR__ . '/../services/PdfService.php';
require_once __DIR__ . '/../config/Database.php';

use setasign\Fpdi\Tcpdf\Fpdi;

class PublicController
{
    private $db;
    private $schedulingService;
    private $pdfService;

    public function __construct()
    {
        error_log("Public Controller instantiated");
        try {
            $this->db = (new Database())->connect();
            if ($this->db === null) {
                throw new Exception("Database connection returned null");
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->schedulingService = new SchedulingService($this->db);
            $this->pdfService = new PdfService();

            error_log("Public Controller initialized successfully");
        } catch (Exception $e) {
            error_log("Public Controller initialization failed: " . $e->getMessage());
            // Don't die here, let the methods handle it gracefully
        }
    }

    public function showHomepage()
    {
        $colleges = $this->fetchColleges();
        $departments = $this->fetchDepartments();
        $programs = $this->fetchPrograms();
        $currentSemester = $this->getCurrentSemester();

        require_once __DIR__ . '/../views/public/home.php';
    }

    private function fetchColleges()
    {
        $query = "SELECT college_id, college_name FROM colleges ORDER BY college_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchDepartments()
    {
        $query = "SELECT department_id, department_name, college_id FROM departments WHERE college_id IS NOT NULL ORDER BY department_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchPrograms()
    {
        $query = "SELECT program_id, program_name, department_id FROM programs ORDER BY program_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchSemesters()
    {
        $query = "SELECT semester_id, semester_name, academic_year FROM semesters ORDER BY year_start DESC, semester_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartmentsByCollege()
    {
        try {
            $college_id = isset($_POST['college_id']) ? (int)$_POST['college_id'] : 0;

            if ($college_id === 0) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }

            $stmt = $this->db->prepare("
                SELECT department_id, department_name 
                FROM departments 
                WHERE college_id = :college_id 
                ORDER BY department_name
            ");

            $stmt->execute([':college_id' => $college_id]);
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($departments);
        } catch (PDOException $e) {
            error_log("Get Departments Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch departments']);
        }
        exit;
    }

    public function getSectionsByDepartment()
    {
        try {
            $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;

            if ($department_id === 0) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }

            $currentSemester = $this->getCurrentSemester();
            $semester_id = $currentSemester['semester_id'];

            $stmt = $this->db->prepare("
                SELECT DISTINCT s.section_id, s.section_name, s.year_level 
                FROM sections s
                JOIN schedules sch ON s.section_id = sch.section_id
                WHERE s.department_id = :department_id 
                AND sch.semester_id = :semester_id
                ORDER BY s.year_level, s.section_name
            ");

            $stmt->execute([
                ':department_id' => $department_id,
                ':semester_id' => $semester_id
            ]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($sections);
        } catch (PDOException $e) {
            error_log("Get Sections Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch sections']);
        }
        exit;
    }

    public function searchSchedules()
    {
        try {
            if ($this->db === null) {
                throw new Exception("Database connection is null");
            }

            $currentSemester = $this->getCurrentSemester();

            if (empty($currentSemester) || !isset($currentSemester['semester_id'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'schedules' => [],
                    'total' => 0,
                    'page' => 1,
                    'per_page' => 10,
                    'error' => 'No active semester is currently set.'
                ]);
                exit;
            }

            // Get basic parameters only
            $college_id = isset($_POST['college_id']) ? (int)$_POST['college_id'] : 0;
            $semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : (int)$currentSemester['semester_id'];
            $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';

            error_log("Online Search - Semester: $semester_id, College: $college_id, Dept: $department_id, Search: '$search'");

            // SIMPLEST POSSIBLE QUERY - No complex functions, no FIELD(), no CASE
            $query = "
        SELECT 
            s.schedule_id, 
            c.course_code, 
            c.course_name, 
            sec.section_name,
            sec.year_level,
            COALESCE(r.room_name, 'Online') AS room_name, 
            s.day_of_week, 
            s.start_time, 
            s.end_time,
            CONCAT(u.first_name, ' ', u.last_name) AS instructor_name,
            d.department_name,
            col.college_name
        FROM schedules s
        JOIN courses c ON s.course_id = c.course_id
        JOIN sections sec ON s.section_id = sec.section_id
        JOIN semesters sem ON s.semester_id = sem.semester_id
        LEFT JOIN classrooms r ON s.room_id = r.room_id
        JOIN faculty f ON s.faculty_id = f.faculty_id
        JOIN users u ON f.user_id = u.user_id
        JOIN departments d ON sec.department_id = d.department_id
        JOIN colleges col ON d.college_id = col.college_id
        WHERE s.is_public = 1
        AND sem.semester_id = ?
        AND (? = 0 OR col.college_id = ?)
        AND (? = 0 OR d.department_id = ?)
        ";

            $params = [$semester_id, $college_id, $college_id, $department_id, $department_id];

            if (!empty($search)) {
                $query .= " AND (c.course_code LIKE ? OR c.course_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
                $searchPattern = '%' . $search . '%';
                $params[] = $searchPattern;
                $params[] = $searchPattern;
                $params[] = $searchPattern;
            }

            // Simple ORDER BY - avoid any functions that might cause issues
            $query .= " ORDER BY c.course_code, sec.section_name, s.day_of_week, s.start_time";

            error_log("Online Query: " . $query);
            error_log("Online Params: " . json_encode($params));

            $stmt = $this->db->prepare($query);

            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }

            // Execute with simple array
            $result = $stmt->execute($params);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Execute failed: " . ($errorInfo[2] ?? 'Unknown'));
            }

            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Online results: " . count($schedules));

            // Simple client-side grouping (let JavaScript handle complex formatting)
            $groupedSchedules = [];
            foreach ($schedules as $schedule) {
                $groupedSchedules[] = [
                    'course_code' => $schedule['course_code'],
                    'course_name' => $schedule['course_name'],
                    'section_name' => $schedule['section_name'],
                    'year_level' => $schedule['year_level'],
                    'room_name' => $schedule['room_name'],
                    'day_of_week' => $schedule['day_of_week'], // Let JS format this
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'instructor_name' => $schedule['instructor_name'],
                    'department_name' => $schedule['department_name'],
                    'college_name' => $schedule['college_name']
                ];
            }

            // Simple pagination
            $total = count($groupedSchedules);
            $perPage = 10;
            $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
            $offset = ($page - 1) * $perPage;
            $pagedSchedules = array_slice($groupedSchedules, $offset, $perPage);

            header('Content-Type: application/json');
            echo json_encode([
                'schedules' => $pagedSchedules,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'debug' => ['online_mode' => true, 'count' => count($schedules)]
            ]);
        } catch (Exception $e) {
            error_log("ONLINE SEARCH ERROR: " . $e->getMessage());

            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'schedules' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 10,
                'error' => 'Service temporarily unavailable. Please try again.',
                'debug' => 'online_error'
            ]);
        }
        exit;
    }

    private function fallbackFormatDays($dayString)
    {
        $dayMap = [
            'Monday' => 'M',
            'Tuesday' => 'T',
            'Wednesday' => 'W',
            'Thursday' => 'TH',
            'Friday' => 'F',
            'Saturday' => 'S',
            'Sunday' => 'SU'
        ];

        $days = explode(', ', $dayString);
        $formatted = '';

        foreach ($days as $day) {
            if (isset($dayMap[$day])) {
                $formatted .= $dayMap[$day];
            }
        }

        // Common combinations
        $combinations = [
            'MWF' => 'MWF',
            'TTH' => 'TTH',
            'MW' => 'MW',
            'THF' => 'THF',
            'MTWTHF' => 'MTWTHF'
        ];

        return $combinations[$formatted] ?? $formatted;
    }

    private function getCurrentSemester()
    {
        $query = "SELECT semester_id, semester_name, academic_year
            FROM semesters
            WHERE is_current = 1
            LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function downloadSchedulePdf()
    {
        try {
            // Check database connection
            if ($this->db === null) {
                throw new Exception("Database connection is null");
            }

            // Check PDF service
            if ($this->pdfService === null) {
                throw new Exception("PDF service is not initialized");
            }

            $currentSemester = $this->getCurrentSemester();

            // Validate current semester
            if (empty($currentSemester) || !isset($currentSemester['semester_id'])) {
                error_log("PDF Download Error: No current semester found");
                die("Error: No active semester configured. Please contact administrator.");
            }

            // Get and validate parameters - using filter_input for safety
            $college_id = filter_input(INPUT_POST, 'college_id', FILTER_VALIDATE_INT) ?: 0;
            $semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_VALIDATE_INT) ?: (int)$currentSemester['semester_id'];
            $department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT) ?: 0;
            $year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
            $section_id = filter_input(INPUT_POST, 'section_id', FILTER_VALIDATE_INT) ?: 0;
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';

            // Debug logging
            error_log("PDF Download - Filters: college=$college_id, dept=$department_id, year=$year_level, section=$section_id, search=$search, semester=$semester_id");

            // Build query with named parameters
            $query = "
            SELECT 
                c.course_code, 
                c.course_name, 
                sec.section_name, 
                sec.year_level,
                COALESCE(r.room_name, 'Online') AS room_name,
                COALESCE(r.building, '') AS building,
                s.day_of_week, 
                s.start_time, 
                s.end_time,
                TRIM(CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name)) AS instructor_name,
                d.department_name, 
                col.college_name
            FROM schedules s
            INNER JOIN courses c ON s.course_id = c.course_id
            INNER JOIN sections sec ON s.section_id = sec.section_id
            INNER JOIN semesters sem ON s.semester_id = sem.semester_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            INNER JOIN faculty f ON s.faculty_id = f.faculty_id
            INNER JOIN users u ON f.user_id = u.user_id
            INNER JOIN departments d ON sec.department_id = d.department_id
            INNER JOIN colleges col ON d.college_id = col.college_id
            WHERE s.is_public = 1
            AND sem.semester_id = :semester_id
        ";

            $params = [':semester_id' => $semester_id];

            // Add optional filters dynamically
            if ($college_id > 0) {
                $query .= " AND col.college_id = :college_id";
                $params[':college_id'] = $college_id;
            }

            if ($department_id > 0) {
                $query .= " AND d.department_id = :department_id";
                $params[':department_id'] = $department_id;
            }

            if (!empty($year_level)) {
                $query .= " AND sec.year_level = :year_level";
                $params[':year_level'] = $year_level;
            }

            if ($section_id > 0) {
                $query .= " AND sec.section_id = :section_id";
                $params[':section_id'] = $section_id;
            }

            if (!empty($search)) {
                $query .= " AND (
                c.course_code LIKE :search 
                OR c.course_name LIKE :search 
                OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search
            )";
                $params[':search'] = '%' . $search . '%';
            }

            $query .= " ORDER BY c.course_code, sec.section_name, s.start_time";

            $stmt = $this->db->prepare($query);

            if (!$stmt) {
                throw new Exception("Failed to prepare SQL statement");
            }

            $stmt->execute($params);
            $rawSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("PDF Download - Raw schedules count: " . count($rawSchedules));

            if (empty($rawSchedules)) {
                error_log("PDF Download - No schedules found with current filters");
                die("No schedules found matching your criteria. Please adjust your filters and try again.");
            }

            // GROUP + FORMAT DAYS
            $grouped = [];
            foreach ($rawSchedules as $sch) {
                $key = implode('|', [
                    $sch['course_code'],
                    $sch['section_name'],
                    $sch['start_time'],
                    $sch['end_time'],
                    $sch['room_name'],
                    $sch['instructor_name']
                ]);

                if (!isset($grouped[$key])) {
                    $grouped[$key] = $sch;
                    $grouped[$key]['days'] = [];
                }
                $grouped[$key]['days'][] = $sch['day_of_week'];
            }

            $schedules = [];
            foreach ($grouped as $item) {
                $dayString = implode(', ', $item['days']);

                // Format days with fallback
                if ($this->schedulingService && method_exists($this->schedulingService, 'formatScheduleDays')) {
                    $item['formatted_days'] = $this->schedulingService->formatScheduleDays($dayString);
                } else {
                    $item['formatted_days'] = $this->fallbackFormatDays($dayString);
                }

                unset($item['days'], $item['day_of_week']);
                $schedules[] = $item;
            }

            error_log("PDF Download - Grouped schedules count: " . count($schedules));

            // BUILD WEEKLY GRID
            $timeSlots = $this->generateTimeSlots();
            $weeklyGrid = $this->buildWeeklyGrid($schedules, $timeSlots);

            // GET FILTER LABELS
            $filters = $this->getFilterLabels($college_id, $department_id, $year_level, $section_id, $search);

            // GENERATE PDF
            error_log("PDF Download - Generating HTML");
            $html = $this->generateWeeklyPdfHtml($weeklyGrid, $timeSlots, $currentSemester, $filters);

            error_log("PDF Download - Converting HTML to PDF");
            $pdfData = $this->pdfService->generateFromHtml($html);

            if (empty($pdfData)) {
                throw new Exception("PDF generation returned empty data");
            }

            error_log("PDF Download - Sending PDF to browser");
            $filename = "PRMSU_Schedule_" . date('Y-m-d') . ".pdf";
            $this->pdfService->sendAsDownload($pdfData, $filename);
        } catch (PDOException $e) {
            error_log("PDF Download PDO Error: " . $e->getMessage());
            http_response_code(500);
            die("Database error occurred while generating PDF. Please try again later.");
        } catch (Exception $e) {
            error_log("PDF Download Error: " . $e->getMessage());
            http_response_code(500);
            die("Error generating PDF: " . $e->getMessage());
        }
    }

    /**
     * Safe version of buildWeeklyGrid with error handling
     */
    private function buildWeeklyGrid($schedules, $timeSlots)
    {
        try {
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $grid = [];
            $occupiedSlots = []; // Track which slots are occupied by multi-slot schedules

            // Initialize grid
            foreach ($timeSlots as $slot) {
                $grid[$slot] = array_fill_keys($days, null);
            }

            foreach ($schedules as $sch) {
                // Validate schedule has required fields
                if (!isset($sch['start_time'], $sch['end_time'], $sch['formatted_days'])) {
                    error_log("Invalid schedule data: " . json_encode($sch));
                    continue;
                }

                // Parse start and end times
                $start = date('g:i A', strtotime($sch['start_time']));
                $end = date('g:i A', strtotime($sch['end_time']));

                // Find ALL affected time slots for this schedule
                $affectedSlots = $this->findAffectedTimeSlots($start, $end, $timeSlots);

                if (empty($affectedSlots)) {
                    error_log("No affected slots found for schedule: {$sch['course_code']} at $start - $end");
                    continue;
                }

                // Calculate row span (how many 30-minute slots this schedule covers)
                $rowSpan = count($affectedSlots);
                $sch['row_span'] = $rowSpan;

                // Expand day abbreviations to full day names
                $dayList = $this->expandDays($sch['formatted_days']);

                // Place schedule in grid (only in first slot, mark others as occupied)
                $firstSlot = $affectedSlots[0];

                foreach ($dayList as $day) {
                    if (in_array($day, $days)) {
                        // Only place the schedule card in the FIRST slot
                        $grid[$firstSlot][$day] = $sch;

                        // Mark all other slots as occupied (so they don't show content)
                        for ($i = 1; $i < count($affectedSlots); $i++) {
                            $occupiedKey = $affectedSlots[$i] . '|' . $day;
                            $occupiedSlots[$occupiedKey] = true;
                        }
                    }
                }
            }

            return ['grid' => $grid, 'occupied' => $occupiedSlots];
        } catch (Exception $e) {
            error_log("Error building weekly grid: " . $e->getMessage());
            // Return empty grid as fallback
            $grid = [];
            foreach ($timeSlots as $slot) {
                $grid[$slot] = array_fill_keys(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], null);
            }
            return ['grid' => $grid, 'occupied' => []];
        }
    }

    private function generateTimeSlots()
    {
        $slots = [];
        $start = strtotime('07:00');
        $end = strtotime('21:00');
        while ($start < $end) {
            $from = date('g:i A', $start);
            $to = date('g:i A', $start + 1800); // 30 mins
            $slots[] = "$from - $to";
            $start += 1800;
        }
        return $slots;
    }

    private function findAffectedTimeSlots($startTime, $endTime, $slots)
    {
        $affectedSlots = [];
        $startTimestamp = strtotime($startTime);
        $endTimestamp = strtotime($endTime);

        foreach ($slots as $slot) {
            // Parse the slot time range
            list($slotStart, $slotEnd) = explode(' - ', $slot);
            $slotStartTimestamp = strtotime($slotStart);
            $slotEndTimestamp = strtotime($slotEnd);

            // Check if this slot is within the schedule's time range
            // A slot is affected if it starts at or after schedule start AND starts before schedule end
            if ($slotStartTimestamp >= $startTimestamp && $slotStartTimestamp < $endTimestamp) {
                $affectedSlots[] = $slot;
            }
        }

        return $affectedSlots;
    }

    private function findTimeSlot($start, $end, $slots)
    {
        $startTimestamp = strtotime($start);

        foreach ($slots as $slot) {
            list($slotStart, $slotEnd) = explode(' - ', $slot);
            $slotStartTimestamp = strtotime($slotStart);

            // Check if the start time matches or is within this slot
            if (abs($startTimestamp - $slotStartTimestamp) < 60) { // Within 1 minute tolerance
                return $slot;
            }
        }

        return null;
    }

    private function expandDays($formatted)
    {
        $map = [
            'MWF' => ['Monday', 'Wednesday', 'Friday'],
            'TTH' => ['Tuesday', 'Thursday'],
            'MW' => ['Monday', 'Wednesday'],
            'THF' => ['Thursday', 'Friday'],
            'MTHF' => ['Monday', 'Thursday', 'Friday'],
            'TWTHF' => ['Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'M' => ['Monday'],
            'T' => ['Tuesday'],
            'W' => ['Wednesday'],
            'Th' => ['Thursday'],
            'F' => ['Friday'],
            'S' => ['Saturday'],
            'Su' => ['Sunday']
        ];
        return $map[$formatted] ?? [$formatted];
    }

    private function getFilterLabels($college_id, $department_id, $year_level, $section_id, $search)
    {
        $labels = [];
        if ($college_id) {
            $stmt = $this->db->prepare("SELECT college_name FROM colleges WHERE college_id = ?");
            $stmt->execute([$college_id]);
            $labels[] = "College: " . $stmt->fetchColumn();
        }
        if ($department_id) {
            $stmt = $this->db->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $stmt->execute([$department_id]);
            $labels[] = "Dept: " . $stmt->fetchColumn();
        }
        if ($year_level) $labels[] = "Year: $year_level";
        if ($section_id) {
            $stmt = $this->db->prepare("SELECT section_name FROM sections WHERE section_id = ?");
            $stmt->execute([$section_id]);
            $labels[] = "Section: " . $stmt->fetchColumn();
        }
        if ($search) $labels[] = "Search: $search";
        return $labels;
    }

    private function generateWeeklyPdfHtml($gridData, $timeSlots, $semester, $filters)
    {
        $grid = $gridData['grid'];
        $occupiedSlots = $gridData['occupied'];

        $university = "President Ramon Magsaysay State University";
        $campus = "Iba Campus";
        $system = "Automated Classroom Scheduling System (ACSS)";
        $semesterName = $semester['semester_name'] . ' ' . $semester['academic_year'];
        $filterText = $filters ? implode(' | ', $filters) : 'All Public Schedules';
        $generated = date('F j, Y \a\t g:i A');

        $logoPath = __DIR__ . '/../../public/assets/logo/main_logo/PRMSUlogo.png';
        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $logo = $logoData ? 'data:image/png;base64,' . $logoData : '';

        ob_start();
        ?>
                <!DOCTYPE html>
                <html>

                <head>
                    <meta charset="UTF-8">
                    <title>Weekly Schedule - <?= htmlspecialchars($university) ?></title>
                    <style>
                        @page {
                            margin: 15mm;
                        }

                        body {
                            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
                            font-size: 9pt;
                            color: #2c3e50;
                            line-height: 1.4;
                            margin: 0;
                        }

                        .container {
                            width: 100%;
                        }

                        .header {
                            text-align: center;
                            border-bottom: 3px solid #DA9100;
                            padding-bottom: 12px;
                            margin-bottom: 15px;
                        }

                        .logo {
                            height: 60px;
                            width: auto;
                            margin-bottom: 8px;
                        }

                        .university {
                            font-size: 16pt;
                            font-weight: bold;
                            color: #DA9100;
                            margin: 0;
                        }

                        .campus {
                            font-size: 12pt;
                            color: #555;
                            margin: 3px 0;
                        }

                        .title {
                            font-size: 14pt;
                            font-weight: bold;
                            color: #2c3e50;
                            margin: 8px 0 4px;
                        }

                        .meta {
                            font-size: 9pt;
                            color: #7f8c8d;
                            margin: 2px 0;
                        }

                        .legend {
                            float: right;
                            font-size: 8pt;
                            background: #f8f9fa;
                            padding: 8px 12px;
                            border-radius: 6px;
                            border: 1px solid #eee;
                            margin-bottom: 15px;
                        }

                        .legend-item {
                            display: inline-block;
                            margin-right: 12px;
                        }

                        .legend-color {
                            display: inline-block;
                            width: 12px;
                            height: 12px;
                            border-radius: 2px;
                            margin-right: 5px;
                            vertical-align: middle;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                            table-layout: fixed;
                        }

                        th,
                        td {
                            border: 1px solid #ddd;
                            padding: 8px 4px;
                            text-align: center;
                            vertical-align: top;
                            font-size: 8pt;
                        }

                        th {
                            background: #f8f9fa;
                            font-weight: bold;
                            color: #2c3e50;
                            font-size: 9pt;
                            height: 40px;
                        }

                        .time-col {
                            background: #eef5db !important;
                            font-weight: bold;
                            width: 10%;
                            font-size: 7.5pt;
                            white-space: nowrap;
                            vertical-align: middle;
                        }

                        .time-col.has-schedule {
                            background: #d4e9ff !important;
                            border-left: 4px solid #0066cc;
                            font-size: 8pt;
                        }

                        .time-col.empty {
                            background: #f8f9fa !important;
                            color: #ccc;
                        }

                        /* Schedule color classes */
                        .mwf {
                            background-color: #d4edda !important;
                            border-left: 3px solid #28a745;
                        }

                        .tth {
                            background-color: #d1ecf1 !important;
                            border-left: 3px solid #17a2b8;
                        }

                        .mw {
                            background-color: #fff3cd !important;
                            border-left: 3px solid #ffc107;
                        }

                        .thf {
                            background-color: #f8d7da !important;
                            border-left: 3px solid #dc3545;
                        }

                        .single {
                            background-color: #e2e3e5 !important;
                            border-left: 3px solid #6c757d;
                        }

                        .course-code {
                            font-weight: bold;
                            font-size: 9pt;
                            color: #2c3e50;
                            margin-bottom: 2px;
                        }

                        .section {
                            font-size: 8pt;
                            color: #495057;
                            margin-bottom: 2px;
                        }

                        .instructor {
                            font-size: 7.5pt;
                            color: #6c757d;
                            font-style: italic;
                            margin-bottom: 2px;
                        }

                        .room {
                            font-size: 7pt;
                            color: #495057;
                            margin-bottom: 3px;
                        }

                        .time-range {
                            font-size: 7pt;
                            color: #0066cc;
                            font-weight: bold;
                            border-top: 1px solid #ddd;
                            padding-top: 3px;
                            margin-top: 3px;
                        }

                        .occupied-cell {
                            background-color: #f8f9fa !important;
                            border-top: none !important;
                        }

                        .footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            height: 40px;
                            border-top: 1px solid #eee;
                            text-align: center;
                            font-size: 8pt;
                            color: #7f8c8d;
                            padding-top: 8px;
                            background: white;
                        }

                        .page-number:after {
                            content: counter(page);
                        }
                    </style>
                </head>

                <body>
                    <div class="container">
                        <div class="header">
                            <?php if ($logo): ?>
                                <img src="<?= $logo ?>" class="logo" alt="Logo">
                            <?php endif; ?>
                            <div class="university"><?= htmlspecialchars($university) ?></div>
                            <div class="campus"><?= htmlspecialchars($campus) ?></div>
                            <div class="title">WEEKLY CLASS SCHEDULE</div>
                            <div class="meta"><?= htmlspecialchars($semesterName) ?></div>
                            <div class="meta"><?= htmlspecialchars($filterText) ?></div>
                            <div class="meta">Generated: <?= $generated ?></div>
                        </div>

                        <div class="legend">
                            <div class="legend-item"><span class="legend-color" style="background:#d4edda"></span>MWF</div>
                            <div class="legend-item"><span class="legend-color" style="background:#d1ecf1"></span>TTH</div>
                            <div class="legend-item"><span class="legend-color" style="background:#fff3cd"></span>MW</div>
                            <div class="legend-item"><span class="legend-color" style="background:#f8d7da"></span>THF</div>
                            <div class="legend-item"><span class="legend-color" style="background:#e2e3e5"></span>Single</div>
                        </div>
                        <div style="clear:both;"></div>

                        <table>
                            <thead>
                                <tr>
                                    <th class="time-col">TIME</th>
                                    <th>MONDAY</th>
                                    <th>TUESDAY</th>
                                    <th>WEDNESDAY</th>
                                    <th>THURSDAY</th>
                                    <th>FRIDAY</th>
                                    <th>SATURDAY</th>
                                    <th>SUNDAY</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timeSlots as $slot):
                                    // Check if any schedule starts in this slot
                                    $hasScheduleStarting = false;
                                    $sampleSchedule = null;

                                    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                                        if (!empty($grid[$slot][$day])) {
                                            $hasScheduleStarting = true;
                                            $sampleSchedule = $grid[$slot][$day];
                                            break;
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td class="time-col <?= $hasScheduleStarting ? 'has-schedule' : 'empty' ?>">
                                            <?php if ($hasScheduleStarting && $sampleSchedule): ?>
                                                <strong><?= htmlspecialchars($slot) ?></strong><br>
                                                <small style="color:#0066cc;">to</small><br>
                                                <strong><?= date('g:i A', strtotime($sampleSchedule['end_time'])) ?></strong>
                                            <?php else: ?>
                                                <span style="color:#ccc;"><?= htmlspecialchars($slot) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day):
                                            $occupiedKey = $slot . '|' . $day;
                                            $isOccupied = isset($occupiedSlots[$occupiedKey]);
                                            $sch = $grid[$slot][$day] ?? null;

                                            if ($isOccupied):
                                                // This cell is part of a schedule from above, skip it
                                        ?>
                                                <td class="occupied-cell"></td>
                                            <?php elseif ($sch):
                                                $bg = $this->getDayColorClass($sch['formatted_days']);
                                                $rowSpan = $sch['row_span'] ?? 1;
                                            ?>
                                                <td class="<?= $bg ?>" rowspan="<?= $rowSpan ?>">
                                                    <div class="course-code"><?= htmlspecialchars($sch['course_code']) ?></div>
                                                    <div class="section"><?= htmlspecialchars($sch['section_name']) ?></div>
                                                    <div class="instructor"><?= htmlspecialchars($sch['instructor_name']) ?></div>
                                                    <div class="room"><?= htmlspecialchars($sch['room_name']) ?></div>
                                                    <div class="time-range">
                                                        <?= date('g:i A', strtotime($sch['start_time'])) ?> -
                                                        <?= date('g:i A', strtotime($sch['end_time'])) ?>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <td></td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="footer">
                            <?= htmlspecialchars($system) ?> | Page <span class="page-number"></span>
                        </div>
                    </div>

                    <script type="text/php">
                        if (isset($pdf)) {
                    $pdf->page_text(750, 570, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 8, array(0,0,0));
                }
            </script>
                </body>

                </html>
        <?php
        return ob_get_clean();
    }

    private function getDayColorClass($days)
    {
        return match (strtoupper($days)) {
            'MWF' => 'mwf',
            'TTH' => 'tth',
            'MW'  => 'mw',
            'THF' => 'thf',
            default => 'single'
        };
    }
}
