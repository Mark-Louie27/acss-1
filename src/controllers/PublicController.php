<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // For TCPDF or LaTeX rendering

use setasign\Fpdi\Tcpdf\Fpdi;

class PublicController
{
    private $db;

    public function __construct()
    {
        error_log("Public Controller instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in Public Controller");
            die("Database connection failed. Please try again later.");
        }
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

    // Add these new methods
    public function getDepartmentSections()
    {
        $currentSemester = $this->getCurrentSemester();
        $departmentId = $_GET['department_id'] ?? 0;

        $query = "SELECT section_id, section_name 
                FROM sections 
                WHERE department_id = :department_id
                AND semester = :semester
                AND academic_year = :academic_year
                ORDER BY section_name";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':semester' => $currentSemester['semester_name'],
                ':academic_year' => $currentSemester['academic_year']
            ]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode($sections);
        } catch (PDOException $e) {
            error_log("Fetch Sections Error: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    public function getCollegeDepartments()
    {
        $collegeId = $_GET['college_id'] ?? 0;
        $query = "SELECT department_id, department_name 
                FROM departments 
                WHERE college_id = :college_id
                ORDER BY department_name";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':college_id' => $collegeId]);
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode($departments);
        } catch (PDOException $e) {
            error_log("Fetch Departments Error: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    // Updated searchSchedules method
    public function searchSchedules()
    {
        $currentSemester = $this->getCurrentSemester();

        $college_id = $_POST['college_id'] ?? 0;
        $semester_id = $_POST['semester_id'] ?? $currentSemester['semester_id'];
        $department_id = $_POST['department_id'] ?? 0;
        $year_level = $_POST['year_level'] ?? '';
        $section_id = $_POST['section_id'] ?? 0;
        $search = $_POST['search'] ?? '';

        $query = "
            SELECT 
                s.schedule_id, 
                c.course_code, 
                c.course_name, 
                sec.section_name,
                sec.year_level,
                r.room_name, 
                r.building, 
                s.day_of_week, 
                s.start_time, 
                s.end_time, 
                s.schedule_type, 
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
            AND sem.semester_id = :semester_id
            AND (col.college_id = :college_id OR :college_id = 0)
            AND (d.department_id = :department_id OR :department_id = 0)
            AND (sec.year_level = :year_level OR :year_level = '')
            AND (sec.section_id = :section_id OR :section_id = 0)
            AND (c.course_code LIKE :search 
                OR c.course_name LIKE :search 
                OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)
            ORDER BY s.day_of_week, s.start_time
        ";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':semester_id' => $currentSemester['semester_id'],
                ':college_id' => $college_id,
                ':department_id' => $department_id,
                ':year_level' => $year_level,
                ':section_id' => $section_id,
                ':search' => '%' . $search . '%'
            ]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['schedules' => $schedules]);
        } catch (PDOException $e) {
            error_log("Search Schedules Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'An error occurred while fetching schedules.']);
        }
        exit;
    }


    private function getCurrentSemester()
    {
        $query = "SELECT * FROM semesters 
              WHERE start_date <= CURDATE() 
              AND end_date >= CURDATE() 
              LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['semester_name' => 'Current', 'academic_year' => date('Y')];
    }

    public function downloadSchedulePDF()
    {
        $college_id = isset($_POST['college_id']) ? (int)$_POST['college_id'] : 0;
        $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        $program_id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
        $year_level = isset($_POST['year_level']) ? $_POST['year_level'] : '';
        $semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0;

        // Fetch schedules (same query as before)
        $query = "
            SELECT 
                s.schedule_id, 
                c.course_code, 
                c.course_name, 
                sec.section_name, 
                r.room_name, 
                r.building, 
                s.day_of_week, 
                s.start_time, 
                s.end_time, 
                s.schedule_type, 
                CONCAT(u.first_name, ' ', u.last_name) AS instructor_name,
                sem.semester_name, 
                sem.academic_year,
                col.college_name,
                d.department_name,
                p.program_name
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            JOIN sections sec ON s.section_id = sec.section_id
            JOIN semesters sem ON s.semester_id = sem.semester_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            JOIN faculty f ON s.faculty_id = f.faculty_id
            JOIN users u ON f.user_id = u.user_id
            JOIN departments d ON sec.department_id = d.department_id
            JOIN colleges col ON d.college_id = col.college_id
            JOIN curriculum_courses cc ON c.course_id = cc.course_id
            JOIN curriculum_programs cp ON cc.curriculum_id = cp.curriculum_id
            JOIN programs p ON cp.program_id = p.program_id
            WHERE s.is_public = 1
            AND (col.college_id = :college_id OR :college_id = 0)
            AND (d.department_id = :department_id OR :department_id = 0)
            AND (cp.program_id = :program_id OR :program_id = 0)
            AND (sec.year_level = :year_level OR :year_level = '')
            AND (s.semester_id = :semester_id OR :semester_id = 0)
            ORDER BY s.day_of_week, s.start_time
        ";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':college_id' => $college_id,
                ':department_id' => $department_id,
                ':program_id' => $program_id,
                ':year_level' => $year_level,
                ':semester_id' => $semester_id
            ]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($schedules)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No schedules found to generate PDF.']);
                exit;
            }

            // Create new PDF document
            $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('PRMSU University');
            $pdf->SetAuthor('PRMSU University');
            $pdf->SetTitle('Class Schedule');
            $pdf->SetSubject('Class Schedule');

            // Add a page
            $pdf->AddPage();

            // Set header and footer fonts
            $pdf->setHeaderFont(array('helvetica', '', 10));
            $pdf->setFooterFont(array('helvetica', '', 8));

            // Set margins
            $pdf->SetMargins(15, 25, 15);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);

            // Set auto page breaks
            $pdf->SetAutoPageBreak(true, 25);

            // Set header content
            $pdf->setHeaderData('', 0, 'PRMSU University', 'Class Schedule');

            // Set font
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Class Schedule', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, $schedules[0]['semester_name'] . ' Semester, ' . $schedules[0]['academic_year'], 0, 1, 'C');
            $pdf->Cell(0, 6, 'College: ' . $schedules[0]['college_name'] . ' | Department: ' . $schedules[0]['department_name'] . ' | Program: ' . $schedules[0]['program_name'], 0, 1, 'C');
            $pdf->Ln(5);

            // Create the table
            $headers = ['Course Code', 'Course Name', 'Section', 'Instructor', 'Room', 'Day', 'Time', 'Type'];
            $columnWidths = [25, 40, 20, 35, 30, 20, 25, 20];

            // Set table font
            $pdf->SetFont('helvetica', 'B', 8);

            // Header
            foreach ($headers as $key => $header) {
                $pdf->Cell($columnWidths[$key], 7, $header, 1, 0, 'C');
            }
            $pdf->Ln();

            // Data
            $pdf->SetFont('helvetica', '', 8);
            foreach ($schedules as $schedule) {
                $room = $schedule['room_name'] ? htmlspecialchars($schedule['room_name'] . ', ' . htmlspecialchars($schedule['building'])) : 'TBD';
                $time = date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time']));

                $pdf->Cell($columnWidths[0], 6, htmlspecialchars($schedule['course_code']), 1);
                $pdf->Cell($columnWidths[1], 6, htmlspecialchars($schedule['course_name']), 1);
                $pdf->Cell($columnWidths[2], 6, htmlspecialchars($schedule['section_name']), 1);
                $pdf->Cell($columnWidths[3], 6, htmlspecialchars($schedule['instructor_name']), 1);
                $pdf->Cell($columnWidths[4], 6, $room, 1);
                $pdf->Cell($columnWidths[5], 6, htmlspecialchars($schedule['day_of_week']), 1);
                $pdf->Cell($columnWidths[6], 6, $time, 1);
                $pdf->Cell($columnWidths[7], 6, htmlspecialchars($schedule['schedule_type']), 1);
                $pdf->Ln();
            }

            // Output the PDF
            $pdf->Output('PRMSU_Schedule_' . date('Ymd_His') . '.pdf', 'D');
        } catch (PDOException $e) {
            error_log("Download Schedule PDF Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'An error occurred while generating the PDF.']);
        } catch (Exception $e) {
            error_log("TCPDF Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to generate PDF.']);
        }
        exit;
    }
}
