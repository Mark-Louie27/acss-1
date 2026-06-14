<?php
require_once __DIR__ . '/../repositories/ScheduleRepository.php';
require_once __DIR__ . '/../repositories/CurriculumRepository.php';

use Src\Repositories\ScheduleRepository;
use Src\Repositories\CurriculumRepository;

class ScheduleService
{
    private PDO $db;
    private ScheduleRepository $scheduleRepo;
    private CurriculumRepository $curriculumRepo;

    // Valid entity fields for conflict checks
    private const ENTITY_FIELDS = ['section_id', 'faculty_id', 'room_id'];

    public function __construct(PDO $db)
    {
        $this->db             = $db;
        $this->scheduleRepo   = new ScheduleRepository($db);
        $this->curriculumRepo = new CurriculumRepository($db);
    }

    // ──────────────────────────────────────────────
    // CONFLICT CHECKING
    // ──────────────────────────────────────────────

    /**
     * Check all three entity conflicts (section, faculty, room) for a given slot.
     * Returns a unique array of conflict message strings.
     */
    public function checkScheduleConflicts(
        int $sectionId,
        int $facultyId,
        ?int $roomId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $semesterId,
        ?int $excludeScheduleId = null
    ): array {
        $conflicts  = [];
        $daysToCheck = $this->expandDayPattern($dayOfWeek) ?: [$dayOfWeek];

        foreach ($daysToCheck as $day) {
            foreach (['section_id' => $sectionId, 'faculty_id' => $facultyId] as $field => $id) {
                $conflicts = array_merge(
                    $conflicts,
                    $this->checkEntityConflicts($field, $id, $day, $startTime, $endTime, $semesterId, $excludeScheduleId)
                );
            }
            if ($roomId) {
                $conflicts = array_merge(
                    $conflicts,
                    $this->checkEntityConflicts('room_id', $roomId, $day, $startTime, $endTime, $semesterId, $excludeScheduleId)
                );
            }
        }
        return array_unique($conflicts);
    }

    private function checkEntityConflicts(
        string $entityField,
        ?int $entityId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $semesterId,
        ?int $excludeScheduleId
    ): array {
        if ($entityId === null || !in_array($entityField, self::ENTITY_FIELDS)) {
            return [];
        }

        $rows      = $this->scheduleRepo->getFacultySchedulesForConflict(
            $entityField,
            $entityId,
            $dayOfWeek,
            $startTime,
            $endTime,
            $semesterId,
            $excludeScheduleId
        );
        $conflicts = [];
        $type      = $this->entityLabel($entityField);

        foreach ($rows as $row) {
            $conflicts[] = "$type conflict: {$row['course_code']} ({$row['section_name']}) "
                . "with {$row['faculty_name']} on {$row['day_of_week']} "
                . "at {$row['start_time']}-{$row['end_time']} [Schedule ID: {$row['schedule_id']}]";
        }
        return $conflicts;
    }

    private function entityLabel(string $field): string
    {
        return match ($field) {
            'section_id' => 'Section',
            'faculty_id' => 'Faculty',
            'room_id'    => 'Room',
            default      => 'Unknown',
        };
    }

    // ──────────────────────────────────────────────
    // SCHEDULE GENERATION
    // ──────────────────────────────────────────────

    /**
     * Main entry point. Clears existing schedules for the given year levels,
     * then generates and persists new ones. Returns the generated schedule rows.
     */
    public function generateSchedules(
        int    $curriculumId,
        array  $yearLevels,
        int    $collegeId,
        array  $currentSemester,
        array  $classrooms,
        array  $faculty,
        int    $departmentId,
        string $semesterType
    ): array {
        $schedules = [];

        $this->db->beginTransaction();
        try {
            // Clear existing
            $deleted = $this->scheduleRepo->deleteByYearLevels(
                $departmentId,
                (int)$currentSemester['semester_id'],
                $yearLevels
            );
            error_log("generateSchedules: Cleared $deleted existing schedules");

            // Load data
            $courses  = $this->curriculumRepo->getCurriculumCourses($curriculumId, $currentSemester['semester_name']);
            $sections = $this->curriculumRepo->getSections($departmentId, (int)$currentSemester['semester_id']);

            $matchingSections = array_values(array_filter(
                $sections,
                fn($s) => in_array($s['year_level'], $yearLevels)
            ));
            $relevantCourses  = array_values(array_filter(
                $courses,
                fn($c) => $c['curriculum_semester'] === $currentSemester['semester_name']
                    && in_array($c['curriculum_year'], $yearLevels)
            ));

            if (empty($matchingSections) || empty($relevantCourses)) {
                $this->db->commit();
                return [];
            }

            $facultySpecs    = $this->curriculumRepo->getFacultySpecializations($departmentId, $collegeId);
            $relevantCourses = $this->sortCoursesBySpecializationPriority($relevantCourses, $facultySpecs);

            // Tracking arrays
            $facultyAssignments    = [];
            $roomAssignments       = [];
            $sectionTracker        = [];
            $usedTimeSlots         = [];
            $scheduledCourses      = [];
            $flexibleSlots         = $this->generateFlexibleTimeSlots();

            foreach ($matchingSections as $sec) {
                $sectionTracker[$sec['section_id']] = [];
            }

            $dayPatterns = [
                'MWF' => ['Monday', 'Wednesday', 'Friday'],
                'TTH' => ['Tuesday', 'Thursday'],
                'SAT' => ['Saturday'],
                'SUN' => ['Sunday'],
                'MW'  => ['Monday', 'Wednesday'],
                'TF'  => ['Tuesday', 'Friday'],
            ];

            $unassigned    = $relevantCourses;
            $maxIterations = 10;

            for ($iter = 0; $iter < $maxIterations && !empty($unassigned); $iter++) {
                $stillUnassigned = [];
                $sorted          = $this->sortCoursesBySpecializationPriority($unassigned, $facultySpecs);

                foreach ($sorted as $idx => $course) {
                    $details     = $this->curriculumRepo->getCourseById((int)$course['course_id']);
                    if (!$details) {
                        $stillUnassigned[] = $course;
                        continue;
                    }

                    $lectureHours = (float)($details['lecture_hours'] ?? 0);
                    $labHours     = (float)($details['lab_hours']     ?? 0);
                    $units        = (float)($details['units']         ?? 3);
                    $hasLecture   = $lectureHours > 0;
                    $hasLab       = $labHours     > 0;
                    $subjectType  = $details['subject_type'] ?? 'Professional Course';
                    $forceF2F     = in_array($subjectType, ['Professional Course', 'Major Course']);

                    $sectionsForCourse = array_values(array_filter(
                        $matchingSections,
                        fn($s) => $s['year_level'] === $course['curriculum_year']
                    ));

                    $isNSTP  = $this->isNSTPCourse($course['course_code']);
                    $hasWkdy = $this->hasAvailableWeekdaySlots($sectionsForCourse, $flexibleSlots, $sectionTracker, $usedTimeSlots);
                    $pattern = $isNSTP ? 'SAT' : $this->selectDayPattern($details, $idx, $hasWkdy);
                    $days    = $dayPatterns[$pattern];

                    $filteredSlots = $this->filterSlotsByDuration($flexibleSlots, $days, $lectureHours, $labHours, $units, $hasLecture, $hasLab);
                    if (empty($filteredSlots)) {
                        $stillUnassigned[] = $course;
                        continue;
                    }

                    $assignedThisCourse = false;

                    foreach ($sectionsForCourse as $section) {
                        $key = $course['course_id'] . '-' . $section['section_id'];
                        if (isset($scheduledCourses[$key])) continue;

                        if ($hasLecture && $hasLab) {
                            $result = $this->scheduleLectureAndLab(
                                $course,
                                $details,
                                $section,
                                $days,
                                $filteredSlots,
                                $facultySpecs,
                                $facultyAssignments,
                                $sectionTracker,
                                $roomAssignments,
                                $usedTimeSlots,
                                $schedules,
                                $currentSemester,
                                $departmentId,
                                $collegeId,
                                $subjectType,
                                $forceF2F
                            );
                        } else {
                            $component = $hasLab ? 'laboratory' : 'lecture';
                            $result    = $this->scheduleSingleComponent(
                                $course,
                                $details,
                                $section,
                                $days,
                                $filteredSlots,
                                $facultySpecs,
                                $facultyAssignments,
                                $sectionTracker,
                                $roomAssignments,
                                $usedTimeSlots,
                                $schedules,
                                $currentSemester,
                                $departmentId,
                                $collegeId,
                                $subjectType,
                                $hasLecture,
                                $hasLab,
                                $forceF2F,
                                $component
                            );
                        }

                        if ($result) {
                            $scheduledCourses[$key] = true;
                            $assignedThisCourse = true;
                        }
                    }

                    if (!$assignedThisCourse) {
                        $stillUnassigned[] = $course;
                    }
                }

                if (count($stillUnassigned) === count($unassigned)) break; // no progress
                $unassigned = $stillUnassigned;
            }

            $this->db->commit();
            return $schedules;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("generateSchedules: Rolled back — " . $e->getMessage());
            return [];
        }
    }

    // ──────────────────────────────────────────────
    // SCHEDULING HELPERS
    // ──────────────────────────────────────────────

    private function scheduleLectureAndLab(
        array $course,
        array $details,
        array $section,
        array $days,
        array $filteredSlots,
        array $facultySpecs,
        array &$facultyAssignments,
        array &$sectionTracker,
        array &$roomAssignments,
        array &$usedTimeSlots,
        array &$schedules,
        array $currentSemester,
        int $departmentId,
        int $collegeId,
        string $subjectType,
        bool $forceF2F
    ): bool {
        $lectureHours = (float)($details['lecture_hours'] ?? 3);
        $labHours     = (float)($details['lab_hours']     ?? 3);
        $dayCount     = count($days);

        $lecDuration = $lectureHours / $dayCount;
        $labDuration = $labHours     / $dayCount;

        $lecSlots = array_values(array_filter($filteredSlots, fn($s) => abs($s[2] - $lecDuration) <= 0.5));
        $labSlots = array_values(array_filter($filteredSlots, fn($s) => abs($s[2] - $labDuration)  <= 0.5));

        $facultyId = $this->findBestFaculty(
            $facultySpecs,
            (int)$course['course_id'],
            $days,
            $filteredSlots[0][0],
            $filteredSlots[0][1],
            $collegeId,
            $departmentId,
            $schedules,
            $facultyAssignments,
            $details['course_code'],
            (int)$section['section_id']
        );
        if (!$facultyId) return false;

        // Find two non-overlapping slots the faculty is free for
        $lecSlot = $labSlot = null;
        foreach ($lecSlots as $ls) {
            foreach ($labSlots as $lbs) {
                if ($ls[0] === $lbs[0] && $ls[1] === $lbs[1]) continue;
                if ($this->hasTimeConflict($ls[0], $ls[1], $lbs[0], $lbs[1]))  continue;
                if (!$this->isSectionSlotFree($section['section_id'], $days, $ls[0], $ls[1], $sectionTracker))  continue;
                if (!$this->isSectionSlotFree($section['section_id'], $days, $lbs[0], $lbs[1], $sectionTracker)) continue;
                if (!$this->isFacultyAvailable($facultyId, $days, $ls[0], $ls[1], $facultyAssignments))  continue;
                if (!$this->isFacultyAvailable($facultyId, $days, $lbs[0], $lbs[1], $facultyAssignments)) continue;
                $lecSlot = $ls;
                $labSlot = $lbs;
                break 2;
            }
        }
        if (!$lecSlot || !$labSlot) return false;

        $lecOk = $this->persistSchedule(
            $course,
            $details,
            $section,
            $days,
            $lecSlot,
            $facultyId,
            'lecture',
            $currentSemester,
            $departmentId,
            $subjectType,
            $forceF2F,
            $schedules,
            $sectionTracker,
            $roomAssignments,
            $usedTimeSlots,
            $facultyAssignments,
            $collegeId
        );
        $labOk = $this->persistSchedule(
            $course,
            $details,
            $section,
            $days,
            $labSlot,
            $facultyId,
            'laboratory',
            $currentSemester,
            $departmentId,
            $subjectType,
            $forceF2F,
            $schedules,
            $sectionTracker,
            $roomAssignments,
            $usedTimeSlots,
            $facultyAssignments,
            $collegeId
        );
        return $lecOk && $labOk;
    }

    private function scheduleSingleComponent(
        array $course,
        array $details,
        array $section,
        array $days,
        array $filteredSlots,
        array $facultySpecs,
        array &$facultyAssignments,
        array &$sectionTracker,
        array &$roomAssignments,
        array &$usedTimeSlots,
        array &$schedules,
        array $currentSemester,
        int $departmentId,
        int $collegeId,
        string $subjectType,
        bool $hasLecture,
        bool $hasLab,
        bool $forceF2F,
        string $component
    ): bool {
        foreach ($filteredSlots as $slot) {
            [$startTime, $endTime] = $slot;

            if (!$this->isSectionSlotFree($section['section_id'], $days, $startTime, $endTime, $sectionTracker)) continue;

            $facultyId = $this->findBestFaculty(
                $facultySpecs,
                (int)$course['course_id'],
                $days,
                $startTime,
                $endTime,
                $collegeId,
                $departmentId,
                $schedules,
                $facultyAssignments,
                $details['course_code'],
                (int)$section['section_id']
            );
            if (!$facultyId) continue;
            if (!$this->isFacultyAvailable($facultyId, $days, $startTime, $endTime, $facultyAssignments)) continue;

            return $this->persistSchedule(
                $course,
                $details,
                $section,
                $days,
                $slot,
                $facultyId,
                $component,
                $currentSemester,
                $departmentId,
                $subjectType,
                $forceF2F,
                $schedules,
                $sectionTracker,
                $roomAssignments,
                $usedTimeSlots,
                $facultyAssignments,
                $collegeId
            );
        }
        return false;
    }

    /**
     * Persist one component (lecture OR lab) across all target days with one room.
     */
    private function persistSchedule(
        array  $course,
        array  $details,
        array  $section,
        array  $days,
        array  $slot,
        int    $facultyId,
        string $component,
        array  $currentSemester,
        int    $departmentId,
        string $subjectType,
        bool   $forceF2F,
        array  &$schedules,
        array  &$sectionTracker,
        array  &$roomAssignments,
        array  &$usedTimeSlots,
        array  &$facultyAssignments,
        int    $collegeId
    ): bool {
        [$startTime, $endTime] = $slot;
        $needsLab = $component === 'laboratory';
        $roomPref = $needsLab ? 'laboratory' : 'classroom';

        // Find ONE room available on ALL days
        $room = null;
        foreach ($days as $day) {
            $candidate = $this->curriculumRepo->findAvailableRoom(
                $departmentId,
                (int)$section['max_students'],
                $day,
                $startTime,
                $endTime,
                (int)$currentSemester['semester_id'],
                $roomPref
            );
            if (!$candidate['room_id']) {
                if ($forceF2F) return false;
                $room = ['room_id' => null, 'room_name' => 'Online'];
                break;
            }
            if ($room === null) {
                $room = $candidate;
            } elseif ($room['room_id'] !== $candidate['room_id']) {
                // Different room on different days — fall back to Online for non-F2F
                if ($forceF2F) return false;
                $room = ['room_id' => null, 'room_name' => 'Online'];
                break;
            }
        }

        // Save each day
        $savedIds = [];
        foreach ($days as $day) {
            $id = $this->scheduleRepo->insert([
                'course_id'      => $course['course_id'],
                'section_id'     => $section['section_id'],
                'room_id'        => $room['room_id'],
                'semester_id'    => $currentSemester['semester_id'],
                'faculty_id'     => $facultyId,
                'schedule_type'  => $room['room_id'] ? 'F2F' : 'Online',
                'day_of_week'    => $day,
                'start_time'     => $startTime,
                'end_time'       => $endTime,
                'status'         => 'Pending',
                'is_public'      => 0,
                'department_id'  => $departmentId,
                'component_type' => $component,
            ]);

            if (!$id) {
                // rollback saved days
                foreach ($savedIds as $rid) {
                    $this->scheduleRepo->deleteById($rid);
                }
                return false;
            }

            $savedIds[] = $id;
            $schedules[] = [
                'schedule_id'    => $id,
                'course_id'      => $course['course_id'],
                'course_code'    => $details['course_code'],
                'section_id'     => $section['section_id'],
                'section_name'   => $section['section_name'],
                'faculty_id'     => $facultyId,
                'room_id'        => $room['room_id'],
                'room_name'      => $room['room_name'] ?? 'Online',
                'day_of_week'    => $day,
                'start_time'     => $startTime,
                'end_time'       => $endTime,
                'component_type' => $component,
                'semester_id'    => $currentSemester['semester_id'],
                'department_id'  => $departmentId,
            ];

            // Update trackers
            $this->updateSectionTracker($sectionTracker, (int)$section['section_id'], $day, $startTime, $endTime);
            $this->updateUsedSlots($usedTimeSlots, $day, $startTime, $endTime);
            if ($room['room_id']) {
                $roomAssignments[$room['room_id']][] = [
                    'day'        => $day,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ];
            }
        }

        $facultyAssignments[] = [
            'faculty_id'   => $facultyId,
            'course_id'    => $course['course_id'],
            'course_code'  => $details['course_code'],
            'section_id'   => $section['section_id'],
            'days'         => $days,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'units'        => (float)($details['units'] ?? 3),
            'component'    => $component,
            'schedule_ids' => $savedIds,
        ];

        return true;
    }

    // ──────────────────────────────────────────────
    // FACULTY SELECTION
    // ──────────────────────────────────────────────

    public function findBestFaculty(
        array  $facultySpecs,
        int    $courseId,
        array  $targetDays,
        string $startTime,
        string $endTime,
        int    $collegeId,
        int    $departmentId,
        array  $schedules,
        array  $facultyAssignments,
        string $courseCode,
        int    $sectionId
    ): ?int {
        $details     = $this->curriculumRepo->getCourseById($courseId);
        $subjectType = $details['subject_type'] ?? 'Professional Course';
        $units       = (float)($details['units'] ?? 3);

        $specialized    = [];
        $nonSpecialized = [];

        foreach ($facultySpecs as $f) {
            $fid = (int)$f['faculty_id'];

            $canTeach = ($subjectType === 'Professional Course' && ($f['can_teach_professional'] ?? false))
                || ($subjectType === 'General Education'   && ($f['can_teach_general']      ?? false));
            if (!$canTeach) continue;

            if (!$this->isFacultyAvailable($fid, $targetDays, $startTime, $endTime, $facultyAssignments)) continue;
            if (!$this->canFacultyTakeMoreLoad($fid, $units, $facultyAssignments, $facultySpecs, $courseCode)) continue;

            $bucket = in_array($courseId, $f['specializations'] ?? []) ? 'specialized' : 'nonSpecialized';
            $$bucket[] = $f;
        }

        // Prefer specialized, fall back to non-specialized
        foreach ([$specialized, $nonSpecialized] as $pool) {
            if (empty($pool)) continue;
            $selected = $this->selectLeastLoaded($pool, $facultyAssignments);
            if ($selected) return (int)$selected['faculty_id'];
        }
        return null;
    }

    private function selectLeastLoaded(array $pool, array $assignments): ?array
    {
        if (empty($pool)) return null;
        usort($pool, function ($a, $b) use ($assignments) {
            $la = $this->calculateFacultyLoad((int)$a['faculty_id'], $assignments, [$a]);
            $lb = $this->calculateFacultyLoad((int)$b['faculty_id'], $assignments, [$b]);
            return $la['units'] <=> $lb['units'];
        });
        return $pool[0];
    }

    // ──────────────────────────────────────────────
    // WORKLOAD
    // ──────────────────────────────────────────────

    public function calculateFacultyLoad(int $facultyId, array $assignments, array $specs = []): array
    {
        $units = $hours = $courses = 0;
        $preps = [];

        foreach ($assignments as $a) {
            if (!isset($a['faculty_id']) || (int)$a['faculty_id'] !== $facultyId) continue;
            $units   += (float)($a['units'] ?? 3);
            $hours   += (float)($a['hours'] ?? 3);
            $courses++;
            $code = $a['course_code'] ?? 'Unknown';
            $preps[$code] = true;
        }

        $empType = 'Regular';
        foreach ($specs as $f) {
            if ((int)$f['faculty_id'] === $facultyId) {
                $empType = $f['employment_type'] ?? 'Regular';
                break;
            }
        }

        return [
            'units'        => $units,
            'hours'        => $hours,
            'courses'      => $courses,
            'preparations' => count($preps),
            'preparation_details' => array_fill_keys(array_keys($preps), ['sections' => []]),
            'employment_type' => $empType,
        ];
    }

    public function canFacultyTakeMoreLoad(
        int $facultyId,
        float $addUnits,
        array $assignments,
        array $specs,
        ?string $newCourseCode = null
    ): bool {
        $load   = $this->calculateFacultyLoad($facultyId, $assignments, $specs);
        $limits = $this->getWorkloadLimits($load['employment_type']);

        $newUnits = $load['units'] + $addUnits;
        $newPreps = $load['preparations'] + (
            ($newCourseCode && !isset($load['preparation_details'][$newCourseCode])) ? 1 : 0
        );

        return $newUnits <= $limits['max_units']
            && $newPreps <= $limits['max_preparations']
            && ($load['courses'] + 1) <= $limits['max_courses'];
    }

    public function getWorkloadLimits(string $employmentType): array
    {
        return match (strtolower(trim($employmentType))) {
            'contractual', 'part-time', 'part time' => [
                'min_units' => 0,
                'max_units' => 42,
                'max_preparations' => 3,
                'max_courses' => 14,
            ],
            default => [
                'min_units' => 0,
                'max_units' => 29,
                'max_preparations' => 3,
                'max_courses' => 8,
            ],
        };
    }

    // ──────────────────────────────────────────────
    // DEADLINE / LOCK
    // ──────────────────────────────────────────────

    public function checkDeadlineStatus(int $departmentId): array
    {
        $deadline = $this->curriculumRepo->getActiveDeadline($departmentId);
        if (!$deadline) return ['locked' => false, 'message' => null];

        $deadlineTime = new DateTime($deadline);
        $now          = new DateTime();

        if ($now > $deadlineTime) {
            return [
                'locked'  => true,
                'message' => 'Schedule creation deadline has passed ('
                    . $deadlineTime->format('M j, Y g:i A') . ')',
            ];
        }

        $diff       = $now->diff($deadlineTime);
        $totalHours = ($diff->days * 24) + $diff->h;
        return [
            'locked'        => false,
            'deadline'      => $deadlineTime,
            'time_remaining' => $diff,
            'total_hours'   => $totalHours,
        ];
    }

    // ──────────────────────────────────────────────
    // VALIDATE INTEGRITY
    // ──────────────────────────────────────────────

    public function validateIntegrity(array $schedules): array
    {
        $conflicts = [];

        // Index by faculty, section, room
        $byFaculty  = [];
        $bySection  = [];
        $byRoom     = [];

        foreach ($schedules as $s) {
            if ($fid = $s['faculty_id'] ?? null)   $byFaculty[$fid][]  = $s;
            if ($sid = $s['section_id'] ?? null)   $bySection[$sid][]  = $s;
            if ($rid = $s['room_id']    ?? null)   $byRoom[$rid][]     = $s;
        }

        $conflicts = array_merge(
            $conflicts,
            $this->findMapConflicts($byFaculty, 'FACULTY_TIME_CONFLICT'),
            $this->findMapConflicts($bySection, 'SECTION_TIME_CONFLICT'),
            $this->findMapConflicts($byRoom,    'ROOM_CONFLICT')
        );
        return $conflicts;
    }

    private function findMapConflicts(array $map, string $type): array
    {
        $conflicts = [];
        foreach ($map as $id => $rows) {
            for ($i = 0; $i < count($rows); $i++) {
                for ($j = $i + 1; $j < count($rows); $j++) {
                    $a = $rows[$i];
                    $b = $rows[$j];
                    if ($a['day_of_week'] !== $b['day_of_week']) continue;
                    if ($this->hasTimeConflict($a['start_time'], $a['end_time'], $b['start_time'], $b['end_time'])) {
                        $conflicts[] = [
                            'type'    => $type,
                            'id'      => $id,
                            'course1' => $a['course_code'] ?? '?',
                            'course2' => $b['course_code'] ?? '?',
                            'day'     => $a['day_of_week'],
                            'time1'   => "{$a['start_time']}-{$a['end_time']}",
                            'time2'   => "{$b['start_time']}-{$b['end_time']}",
                        ];
                    }
                }
            }
        }
        return $conflicts;
    }

    // ──────────────────────────────────────────────
    // TIME / SLOT UTILITIES
    // ──────────────────────────────────────────────

    public function hasTimeConflict(string $s1, string $e1, string $s2, string $e2): bool
    {
        return strtotime($s1) < strtotime($e2) && strtotime($s2) < strtotime($e1);
    }

    public function expandDayPattern(string $pattern): array
    {
        $map = [
            'MWF'  => ['Monday', 'Wednesday', 'Friday'],
            'TTH'  => ['Tuesday', 'Thursday'],
            'TH'   => ['Tuesday', 'Thursday'],
            'MW'   => ['Monday', 'Wednesday'],
            'TF'   => ['Tuesday', 'Friday'],
            'SAT'  => ['Saturday'],
            'SUN'  => ['Sunday'],
            'M'    => ['Monday'],
            'T'    => ['Tuesday'],
            'W'    => ['Wednesday'],
            'R'    => ['Thursday'],
            'F'    => ['Friday'],
            'S'    => ['Saturday'],
        ];
        return $map[strtoupper($pattern)] ?? [];
    }

    public function generateFlexibleTimeSlots(): array
    {
        $slots      = [];
        $startTimes = [
            '07:30:00',
            '08:00:00',
            '08:30:00',
            '09:00:00',
            '10:30:00',
            '12:30:00',
            '13:00:00',
            '14:30:00',
            '15:00:00',
            '15:30:00',
            '16:00:00',
            '17:30:00'
        ];
        $durations  = [1, 1.5, 2];

        foreach ($startTimes as $start) {
            $ts = strtotime($start);
            foreach ($durations as $dur) {
                $end = date('H:i:s', $ts + (int)($dur * 3600));
                if (strtotime($end) <= strtotime('21:00:00')) {
                    $slots[] = [$start, $end, $dur];
                }
            }
        }
        return $slots;
    }

    private function filterSlotsByDuration(
        array $slots,
        array $days,
        float $lectureHours,
        float $labHours,
        float $units,
        bool $hasLecture,
        bool $hasLab
    ): array {
        $dayCount    = count($days);
        $lecDuration = $hasLecture ? ($lectureHours > 0 ? $lectureHours / $dayCount : $units / $dayCount) : 0;
        $labDuration = $hasLab     ? ($labHours     > 0 ? $labHours     / $dayCount : $units / $dayCount) : 0;

        $filtered = array_filter($slots, function ($slot) use ($lecDuration, $labDuration, $units, $dayCount, $hasLecture, $hasLab) {
            $d = $slot[2];
            return ($hasLecture && abs($d - $lecDuration) <= 0.5)
                || ($hasLab     && abs($d - $labDuration) <= 0.5)
                || (!$hasLecture && !$hasLab && abs($d - $units / max($dayCount, 1)) <= 0.5);
        });

        return array_values($filtered);
    }

    private function isSectionSlotFree(int $sectionId, array $days, string $start, string $end, array $tracker): bool
    {
        if (!isset($tracker[$sectionId])) return true;
        foreach ($days as $day) {
            foreach ($tracker[$sectionId] as $existing) {
                if ($existing['day'] === $day && $this->hasTimeConflict($start, $end, $existing['start_time'], $existing['end_time'])) {
                    return false;
                }
            }
        }
        return true;
    }

    public function isFacultyAvailable(int $facultyId, array $days, string $start, string $end, array $assignments): bool
    {
        foreach ($assignments as $a) {
            if (!isset($a['faculty_id']) || (int)$a['faculty_id'] !== $facultyId) continue;
            foreach ($days as $day) {
                if (in_array($day, (array)($a['days'] ?? [])) && $this->hasTimeConflict($start, $end, $a['start_time'], $a['end_time'])) {
                    return false;
                }
            }
        }
        return true;
    }

    private function updateSectionTracker(array &$tracker, int $sectionId, string $day, string $start, string $end): void
    {
        $tracker[$sectionId][] = ['day' => $day, 'start_time' => $start, 'end_time' => $end];
    }

    private function updateUsedSlots(array &$used, string $day, string $start, string $end): void
    {
        $key = "{$day}_{$start}_{$end}";
        $used[$key] = ($used[$key] ?? 0) + 1;
    }

    private function hasAvailableWeekdaySlots(array $sections, array $slots, array $tracker, array $used): bool
    {
        $patterns = [['Monday', 'Wednesday', 'Friday'], ['Tuesday', 'Thursday'], ['Monday', 'Wednesday'], ['Tuesday', 'Friday']];
        foreach ($patterns as $days) {
            foreach ($slots as $slot) {
                foreach ($sections as $sec) {
                    if ($this->isSectionSlotFree((int)$sec['section_id'], $days, $slot[0], $slot[1], $tracker)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function selectDayPattern(array $details, int $idx, bool $hasWeekday): string
    {
        if ($hasWeekday) {
            return $idx % 2 === 0 ? 'MWF' : 'TTH';
        }
        return $idx % 2 === 0 ? 'SAT' : 'SUN';
    }

    public function isNSTPCourse(string $courseCode): bool
    {
        return (bool)preg_match('/^(?:NSTP|CWTS|ROTC|LTS)\s*-?\s*(?:[0-9]+|I{1,3})?$/i', trim($courseCode));
    }

    // ──────────────────────────────────────────────
    // COURSE SORTING
    // ──────────────────────────────────────────────

    private function sortCoursesBySpecializationPriority(array $courses, array $specs): array
    {
        $withPriority = array_map(function ($course) use ($specs) {
            $count = 0;
            foreach ($specs as $f) {
                if (in_array($course['course_id'], $f['specializations'] ?? [])) $count++;
            }
            $details = $this->curriculumRepo->getCourseById((int)$course['course_id']);
            return [
                'course'       => $course,
                'spec_count'   => $count,
                'is_prof'      => ($details['subject_type'] ?? '') === 'Professional Course',
                'course_code'  => $course['course_code'],
            ];
        }, $courses);

        usort($withPriority, function ($a, $b) {
            $hasA = $a['spec_count'] > 0;
            $hasB = $b['spec_count'] > 0;
            if ($hasA !== $hasB) return $hasA ? -1 : 1;
            if ($hasA && $hasB && $a['spec_count'] !== $b['spec_count']) {
                return $a['spec_count'] <=> $b['spec_count'];
            }
            if ($a['is_prof'] !== $b['is_prof']) return $a['is_prof'] ? -1 : 1;
            return $a['course_code'] <=> $b['course_code'];
        });

        return array_column($withPriority, 'course');
    }

    // ──────────────────────────────────────────────
    // CONFLICT TYPE HELPERS (used by controllers)
    // ──────────────────────────────────────────────

    public function getConflictType(string $message): string
    {
        if (str_contains($message, 'Section')) return 'section';
        if (str_contains($message, 'Faculty')) return 'faculty';
        if (str_contains($message, 'Room'))    return 'room';
        return 'time';
    }

    public function getConflictSeverity(string $message): string
    {
        if (str_contains($message, 'Section')) return 'high';
        if (str_contains($message, 'Faculty')) return 'medium';
        if (str_contains($message, 'Room'))    return 'low';
        return 'medium';
    }
}
