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
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in Public Controller");
            die("Database connection failed. Please try again later.");
        }

        $this->schedulingService = new SchedulingService($this->db);
        $this->pdfService = new PdfService();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
        $currentSemester = $this->getCurrentSemester();

        $college_id = isset($_POST['college_id']) ? (int)$_POST['college_id'] : 0;
        $semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : $currentSemester['semester_id'];
        $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        $year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
        $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';

        $query = "
                SELECT 
                    s.schedule_id, 
                    c.course_code, 
                    c.course_name, 
                    sec.section_name,
                    sec.year_level,
                    COALESCE(r.room_name, 'Online') AS room_name, 
                    r.building, 
                    s.day_of_week, 
                    s.start_time, 
                    s.end_time, 
                    s.schedule_type, 
                    TRIM(CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name)) AS instructor_name,
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
                AND (? = '' OR sec.year_level = ?)
                AND (? = 0 OR sec.section_id = ?)
                AND (c.course_code LIKE ? OR c.course_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)
                ORDER BY c.course_code, sec.section_name, s.start_time, FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
            ";

        try {
            $stmt = $this->db->prepare($query);
            $searchPattern = '%' . $search . '%';

            $stmt->execute([
                $semester_id,
                $college_id,
                $college_id,
                $department_id,
                $department_id,
                $year_level,
                $year_level,
                $section_id,
                $section_id,
                $searchPattern,
                $searchPattern,
                $searchPattern
            ]);

            $rawSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // GROUP BY: course + section + time + room → collect days
            $grouped = [];
            foreach ($rawSchedules as $sch) {
                $key = $sch['course_code'] . '|' .
                    $sch['section_name'] . '|' .
                    $sch['start_time'] . '|' .
                    $sch['end_time'] . '|' .
                    $sch['room_name'] . '|' .
                    $sch['instructor_name'];

                if (!isset($grouped[$key])) {
                    $grouped[$key] = $sch;
                    $grouped[$key]['days'] = [];
                }
                $grouped[$key]['days'][] = $sch['day_of_week'];
            }

            // FORMAT DAYS using SchedulingService
            $formattedSchedules = [];
            foreach ($grouped as $item) {
                $dayString = implode(', ', $item['days']);
                $item['formatted_days'] = $this->schedulingService->formatScheduleDays($dayString);
                unset($item['days'], $item['day_of_week']); // Remove raw
                $formattedSchedules[] = $item;
            }

            // Sort by formatted days + time
            usort($formattedSchedules, function ($a, $b) {
                $daysOrder = ['MWF' => 1, 'TTH' => 2, 'MW' => 3, 'THF' => 4, 'MTHF' => 5, 'TWTHF' => 6];
                $aKey = $a['formatted_days'];
                $bKey = $b['formatted_days'];
                $aOrder = $daysOrder[$aKey] ?? 99;
                $bOrder = $daysOrder[$bKey] ?? 99;
                if ($aOrder !== $bOrder) return $aOrder <=> $bOrder;
                return $a['start_time'] <=> $b['start_time'];
            });

            $total = count($formattedSchedules);
            $perPage = 10;
            $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
            $offset = ($page - 1) * $perPage;
            $pagedSchedules = array_slice($formattedSchedules, $offset, $perPage);

            header('Content-Type: application/json');
            echo json_encode([
                'schedules' => $pagedSchedules,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'debug' => [
                    'raw_count' => count($rawSchedules),
                    'grouped_count' => count($formattedSchedules)
                ]
            ]);
        } catch (PDOException $e) {
            error_log("Search Schedules Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database error.']);
        }
        exit;
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
        // Re-use search logic
        $currentSemester = $this->getCurrentSemester();

        $college_id = $_POST['college_id'] ?? 0;
        $semester_id = $_POST['semester_id'] ?? $currentSemester['semester_id'];
        $department_id = $_POST['department_id'] ?? 0;
        $year_level = $_POST['year_level'] ?? '';
        $section_id = $_POST['section_id'] ?? 0;
        $search = $_POST['search'] ?? '';

        $query = "
        SELECT 
            c.course_code, c.course_name, sec.section_name, sec.year_level,
            COALESCE(r.room_name, 'Online') AS room_name,
            s.day_of_week, s.start_time, s.end_time,
            TRIM(CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name)) AS instructor_name,
            d.department_name, col.college_name
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
          AND (? = '' OR sec.year_level = ?)
          AND (? = 0 OR sec.section_id = ?)
          AND (c.course_code LIKE ? OR c.course_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)
        ORDER BY c.course_code, sec.section_name, s.start_time
    ";

        $stmt = $this->db->prepare($query);
        $searchPattern = '%' . $search . '%';
        $stmt->execute([
            $semester_id,
            $college_id,
            $college_id,
            $department_id,
            $department_id,
            $year_level,
            $year_level,
            $section_id,
            $section_id,
            $searchPattern,
            $searchPattern,
            $searchPattern
        ]);

        $rawSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // GROUP + FORMAT DAYS
        $grouped = [];
        foreach ($rawSchedules as $sch) {
            $key = $sch['course_code'] . '|' . $sch['section_name'] . '|' . $sch['start_time'] . '|' . $sch['end_time'] . '|' . $sch['room_name'] . '|' . $sch['instructor_name'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = $sch;
                $grouped[$key]['days'] = [];
            }
            $grouped[$key]['days'][] = $sch['day_of_week'];
        }

        $schedules = [];
        foreach ($grouped as $item) {
            $dayString = implode(', ', $item['days']);
            $item['formatted_days'] = $this->schedulingService->formatScheduleDays($dayString);
            unset($item['days'], $item['day_of_week']);
            $schedules[] = $item;
        }

        // BUILD WEEKLY GRID
        $timeSlots = $this->generateTimeSlots(); // 7:00 AM - 9:00 PM
        $weeklyGrid = $this->buildWeeklyGrid($schedules, $timeSlots);

        // GET FILTER LABELS
        $filters = $this->getFilterLabels($college_id, $department_id, $year_level, $section_id, $search);

        // GENERATE PDF
        $html = $this->generateWeeklyPdfHtml($weeklyGrid, $timeSlots, $currentSemester, $filters);
        $pdfData = $this->pdfService->generateFromHtml($html);

        $filename = "PRMSU_Schedule_" . date('Y-m-d') . ".pdf";
        $this->pdfService->sendAsDownload($pdfData, $filename);
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

    private function buildWeeklyGrid($schedules, $timeSlots)
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $grid = [];

        foreach ($timeSlots as $slot) {
            $grid[$slot] = array_fill_keys($days, null);
        }

        foreach ($schedules as $sch) {
            list($start, $end) = explode(' - ', $sch['start_time'] . ' - ' . $sch['end_time']);
            $startKey = date('g:i A', strtotime($start));
            $endKey = date('g:i A', strtotime($end));

            $slotKey = $this->findTimeSlot($startKey, $endKey, $timeSlots);
            if (!$slotKey) continue;

            $dayList = $this->expandDays($sch['formatted_days']);
            foreach ($dayList as $day) {
                if (in_array($day, $days)) {
                    $grid[$slotKey][$day] = $sch;
                }
            }
        }

        return $grid;
    }

    private function findTimeSlot($start, $end, $slots)
    {
        foreach ($slots as $slot) {
            if (strpos($slot, $start) === 0) return $slot;
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
            'S' => ['Saturday']
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

    private function generateWeeklyPdfHtml($grid, $timeSlots, $semester, $filters)
    {
        $university = "President Ramon Magsaysay State University";
        $campus = "Iba Campus";
        $system = "Automatic Classroom Scheduling System (ACSS)";
        $semesterName = $semester['semester_name'] . ' ' . $semester['academic_year'];
        $filterText = $filters ? implode(' | ', $filters) : 'All Public Schedules';
        $generated = date('F j, Y \a\t g:i A');

        // Load logo as base64
        $logoPath = __DIR__ . '/../../public/assets/logo/main_logo/PRMSUlogo.png';
        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $logo = $logoData ? 'data:image/png;base64,' . $logoData : '';

        ob_start(); ?>
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

                /* Header */
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

                /* Legend */
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

                /* Table */
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                    table-layout: fixed;
                }

                th,
                td {
                    border: 1px solid #ddd;
                    padding: 6px 4px;
                    text-align: center;
                    vertical-align: top;
                    height: 70px;
                    font-size: 8pt;
                    position: relative;
                }

                th {
                    background: #f8f9fa;
                    font-weight: bold;
                    color: #2c3e50;
                    font-size: 9pt;
                }

                .time-col {
                    background: #eef5db !important;
                    font-weight: bold;
                    width: 10%;
                    writing-mode: vertical-rl;
                    text-orientation: mixed;
                    transform: rotate(180deg);
                    white-space: nowrap;
                }

                /* Day Pattern Colors */
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
                }

                .section {
                    font-size: 8pt;
                    color: #495057;
                }

                .instructor {
                    font-size: 7.5pt;
                    color: #6c757d;
                    font-style: italic;
                }

                .room {
                    font-size: 7pt;
                    color: #495057;
                }

                /* Footer */
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
                <!-- Header -->
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

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item"><span class="legend-color" style="background:#d4edda"></span>MWF</div>
                    <div class="legend-item"><span class="legend-color" style="background:#d1ecf1"></span>TTH</div>
                    <div class="legend-item"><span class="legend-color" style="background:#fff3cd"></span>MW</div>
                    <div class="legend-item"><span class="legend-color" style="background:#f8d7da"></span>THF</div>
                    <div class="legend-item"><span class="legend-color" style="background:#e2e3e5"></span>Single</div>
                </div>
                <div style="clear:both;"></div>

                <!-- Schedule Table -->
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $slot): ?>
                            <tr>
                                <td class="time-col"><?= $slot ?></td>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day):
                                    $sch = $grid[$slot][$day] ?? null;
                                    $bg = $sch ? $this->getDayColorClass($sch['formatted_days']) : '';
                                ?>
                                    <td class="<?= $bg ?>">
                                        <?php if ($sch): ?>
                                            <div class="course-code"><?= htmlspecialchars($sch['course_code']) ?></div>
                                            <div class="section"><?= htmlspecialchars($sch['section_name']) ?></div>
                                            <div class="instructor"><?= htmlspecialchars($sch['instructor_name']) ?></div>
                                            <div class="room"><?= htmlspecialchars($sch['room_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Footer -->
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
