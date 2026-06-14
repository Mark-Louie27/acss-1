<?php
ob_start();

$activeTab = $_GET['tab'] ?? 'generate';
$activeTab = in_array($activeTab, ['generate', 'manual', 'schedule-list']) ? $activeTab : 'generate';

// Get department ID
$userDepartmentId = $_SESSION['department_id'] ?? null;
// Fetch college logo based on department ID
$collegeLogoPath = '/assets/logo/main_logo/PRMSUlogo.png'; // Fallback to university logo
if ($userDepartmentId) {
    try {
        $db = (new Database())->connect();
        $stmt = $db->prepare("SELECT c.logo_path FROM colleges c JOIN departments d ON c.college_id = d.college_id WHERE d.department_id = :department_id");
        $stmt->execute([':department_id' => $userDepartmentId]);
        $logoPath = $stmt->fetchColumn();
        if ($logoPath) {
            $collegeLogoPath = $logoPath;
        }
    } catch (PDOException $e) {
        error_log("layout: Error fetching college logo - " . $e->getMessage());
    }
}

if (!isset($schedules) || !is_array($schedules)) {
    $schedules = [];
}
?>

<link rel="stylesheet" href="/css/schedule_management.css">

<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center space-x-4">
                    <div class="bg-yellow-500 p-3 rounded-lg">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Schedule Management</h1>
                        <p class="text-sm text-gray-600">Organize and manage academic schedules</p>
                    </div>
                </div>
                <!-- Print Options -->
                <div class="relative">
                    <button id="printDropdownBtn" onclick="togglePrintDropdown()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                        <i class="fas fa-print"></i>
                        <span>Print/Export</span>
                        <i class="fas fa-chevron-down ml-1"></i>
                    </button>
                    <div id="printDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                        <div class="py-1">
                            <button onclick="printSchedule('all')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-print mr-2"></i>Print All Schedules
                            </button>
                            <button onclick="printSchedule('filtered')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-filter mr-2"></i>Print Filtered View
                            </button>
                            <div class="border-t border-gray-200 my-1"></div>
                            <button onclick="exportSchedule('excel')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-excel mr-2 text-green-600"></i>Export to Excel
                            </button>
                            <button onclick="exportSchedule('pdf')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-pdf mr-2 text-red-600"></i>Export to PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Notifications -->
        <?php if (isset($error)): ?>
            <div class="mb-6 flex items-center p-4 bg-red-50 border border-red-200 rounded-lg">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                <p class="text-sm font-medium text-red-800"><?php echo nl2br(htmlspecialchars($error)); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="mb-6 flex items-center p-4 bg-green-50 border border-green-200 rounded-lg">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <!-- Add this after your notifications section -->
        <?php
        // Check if there are unassigned courses or incomplete schedules
        $hasIncompleteSchedules = false;
        $unassignedCourses = [];

        if (isset($schedules) && is_array($schedules)) {
            // Get all curriculum courses for current semester
            $allCurriculumCourses = $jsData['curriculumCourses'] ?? [];
            $scheduledCourseCodes = array_unique(array_column($schedules, 'course_code'));

            // Find unscheduled courses
            foreach ($allCurriculumCourses as $course) {
                if (!in_array($course['course_code'], $scheduledCourseCodes)) {
                    $unassignedCourses[] = $course;
                }
            }

            $hasIncompleteSchedules = !empty($unassignedCourses);
        }
        ?>

        <!-- Incomplete Schedule Warning Banner -->
        <?php if ($hasIncompleteSchedules && $activeTab === 'manual'): ?>
            <div id="incomplete-schedule-banner" class="mb-6 flex items-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-yellow-800">Incomplete Schedule Detected</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        <?php echo count($unassignedCourses); ?> course(s) could not be scheduled automatically.
                        You may need to manually schedule these courses.
                    </p>
                    <div class="mt-2">
                        <button onclick="toggleUnassignedCourses()" class="text-sm text-yellow-600 hover:text-yellow-800 font-medium flex items-center">
                            <span>View Unscheduled Courses</span>
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                    </div>
                    <div id="unassigned-courses-list" class="hidden mt-3 p-3 bg-yellow-100 rounded-lg">
                        <h4 class="text-sm font-semibold text-yellow-800 mb-2">Unscheduled Courses:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            <?php foreach ($unassignedCourses as $course): ?>
                                <div class="text-sm text-yellow-700 p-2 bg-white rounded border border-yellow-200">
                                    <div class="font-medium"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                    <div class="text-xs"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    <div class="text-xs text-yellow-600 mt-1">
                                        Year: <?php echo htmlspecialchars($course['curriculum_year']); ?> •
                                        Units: <?php echo htmlspecialchars($course['units']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 flex space-x-2">
                            <button onclick="tryRegenerateIncomplete()" class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded transition-colors">
                                <i class="fas fa-sync-alt mr-1"></i>Try Regenerate
                            </button>
                            <button onclick="hideIncompleteWarning()" class="px-3 py-1 bg-gray-500 hover:bg-gray-600 text-white text-sm rounded transition-colors">
                                <i class="fas fa-times mr-1"></i>Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <div class="mb-8">
            <nav class="flex space-x-1 bg-white rounded-lg p-1 shadow-sm border border-gray-200">
                <button onclick="switchTab('generate')" id="tab-generate" class="tab-button flex-1 py-3 px-4 text-sm font-medium rounded-md transition-all duration-200 <?php echo $activeTab === 'generate' ? 'bg-yellow-500 text-white' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-magic mr-2"></i>
                    <span class="hidden sm:inline">Generate Schedules</span>
                    <span class="sm:hidden">Generate</span>
                </button>
                <button onclick="switchTab('manual')" id="tab-manual" class="tab-button flex-1 py-3 px-4 text-sm font-medium rounded-md transition-all duration-200 <?php echo $activeTab === 'manual' ? 'bg-yellow-500 text-white' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-edit mr-2"></i>
                    <span class="hidden sm:inline">Manual Edit</span>
                    <span class="sm:hidden">Manual</span>
                </button>
                <button onclick="switchTab('schedule')" id="tab-schedule" class="tab-button flex-1 py-3 px-4 text-sm font-medium rounded-md transition-all duration-200 <?php echo $activeTab === 'schedule-list' ? 'bg-yellow-500 text-white' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-calendar mr-2"></i>
                    <span class="hidden sm:inline">View Schedule</span>
                    <span class="sm:hidden">View</span>
                </button>
            </nav>
        </div>

        <!-- Generate Tab -->
        <div id="content-generate" class="tab-content <?php echo $activeTab !== 'generate' ? 'hidden' : ''; ?>">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="bg-yellow-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-magic text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Generate Schedules</h2>
                </div>

                <form id="generate-form" class="space-y-6">
                    <input type="hidden" name="tab" value="generate">
                    <input type="hidden" name="semester_id" value="<?php echo htmlspecialchars($currentSemester['semester_id'] ?? ''); ?>">

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                            <span class="text-sm font-medium text-gray-800">Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?> Semester</span>
                            <span class="text-sm font-medium text-gray-800 ml-4">A.Y <?php echo htmlspecialchars($currentSemester['academic_year'] ?? 'Not Set'); ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Curriculum</label>
                            <select name="curriculum_id" id="curriculum_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white" onchange="updateCourses()" required>
                                <option value="">Select Curriculum</option>
                                <?php foreach ($curricula as $curriculum): ?>
                                    <option value="<?php echo htmlspecialchars($curriculum['curriculum_id']); ?>">
                                        <?php echo htmlspecialchars($curriculum['curriculum_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Courses</h3>
                            <div id="courses-list" class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 h-80 overflow-y-auto">
                                <p class="text-sm text-gray-600">Please select a curriculum to view available courses.</p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Sections</h3>
                            <div id="sections-list" class="bg-white border scroll-auto border-gray-200 rounded-lg shadow-sm p-4 h-80 overflow-y-auto">
                                <?php if (!empty($jsData['sectionsData'])): ?>
                                    <ul class="list-disc pl-5 text-sm text-gray-700">
                                        <?php foreach ($jsData['sectionsData'] as $section): ?>
                                            <li class="py-1">
                                                <?php echo htmlspecialchars($section['section_name']); ?> -
                                                <?php echo htmlspecialchars($section['year_level']); ?>
                                                (Students: <?php echo htmlspecialchars($section['current_students']); ?>/<?php echo htmlspecialchars($section['max_students']); ?>)
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-sm text-red-600">No sections found for the current semester.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" id="generate-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-8 py-3 rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-magic mr-2"></i>
                            Generate Schedules
                        </button>
                    </div>
                </form>

                <!-- Generation Results -->
                <div id="generation-results" class="hidden mt-8 p-6 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-green-800">Schedules Generated Successfully!</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white p-3 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600" id="total-courses">0</div>
                            <div class="text-gray-600">Courses Scheduled</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600" id="total-sections">0</div>
                            <div class="text-gray-600">Sections</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600" id="success-rate">100%</div>
                            <div class="text-gray-600">Success Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fixed Manual Edit Tab -->
        <div id="content-manual" class="tab-content <?php echo $activeTab !== 'manual' ? 'hidden' : ''; ?>">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <!-- Header Section -->
                <div class="mb-6">
                    <!-- Title Row -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                        <div class="flex items-center">
                            <div class="bg-yellow-500 p-2 rounded-lg mr-3">
                                <i class="fas fa-edit text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Manual Schedule Editor</h2>
                                <p class="text-sm text-gray-600 mt-1">Drag and drop to edit schedules</p>
                            </div>
                        </div>

                        <!-- Full Screen Buttons -->
                        <div class="flex items-center space-x-2">
                            <button id="fullscreen-manual-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="toggleFullScreen('manual')">
                                <i class="fas fa-expand mr-1"></i>
                                <span class="hidden sm:inline">Full Screen</span>
                            </button>
                            <button id="exit-fullscreen-manual-btn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors hidden text-sm" onclick="toggleFullScreen('manual')">
                                <i class="fas fa-compress mr-1"></i>
                                <span class="hidden sm:inline">Exit Full Screen</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filters Row -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-filter mr-2 text-yellow-600"></i>
                            Filter Schedules
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Year Level</label>
                                <select id="filter-year-manual" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm" onchange="filterSchedulesManual()">
                                    <option value="">All Year Levels</option>
                                    <?php $yearLevels = array_unique(array_column($schedules, 'year_level')); ?>
                                    <?php foreach ($yearLevels as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                                <select id="filter-section-manual" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm" onchange="filterSchedulesManual()">
                                    <option value="">All Sections</option>
                                    <?php $sectionNames = array_unique(array_column($schedules, 'section_name')); ?>
                                    <?php foreach ($sectionNames as $section): ?>
                                        <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Room</label>
                                <select id="filter-room-manual" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm" onchange="filterSchedulesManual()">
                                    <option value="">All Rooms</option>
                                    <?php $rooms = array_unique(array_column($schedules, 'room_name')); ?>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo htmlspecialchars($room ?? 'Online'); ?>"><?php echo htmlspecialchars($room ?? 'Online'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button onclick="clearFiltersManual()" class="w-full px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                                    <i class="fas fa-times mr-1"></i>
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="flex flex-wrap gap-2 justify-between items-center">
                        <div class="flex flex-wrap gap-2">
                            <button id="add-schedule-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="openAddModal()">
                                <i class="fas fa-plus"></i>
                                <span>Add Schedule</span>
                            </button>

                            <button id="toggle-view-btn" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="toggleViewMode()">
                                <i class="fas fa-list mr-1"></i>
                                <span>List View</span>
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <!-- View Toggle Button -->
                            <button id="delete-all-btn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="deleteAllSchedules()">
                                <i class="fas fa-trash"></i>
                                <span>Delete All</span>
                            </button>

                            <button onclick="refreshManualView()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- GRID VIEW -->
                <div id="grid-view" class="block">
                    <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="min-w-full">
                            <!-- Header with days -->
                            <div class="grid grid-cols-8 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                                <div class="px-3 py-3 text-sm font-semibold text-gray-700 border-r border-gray-200 bg-gray-50 sticky left-0 z-10">
                                    <span class="hidden sm:inline">Time</span>
                                    <span class="sm:hidden">⌚</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                    <span class="hidden sm:inline">Monday</span>
                                    <span class="sm:hidden">Mon</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                    <span class="hidden sm:inline">Tuesday</span>
                                    <span class="sm:hidden">Tue</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                    <span class="hidden sm:inline">Wednesday</span>
                                    <span class="sm:hidden">Wed</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                    <span class="hidden sm:inline">Thursday</span>
                                    <span class="sm:hidden">Thu</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                    <span class="hidden sm:inline">Friday</span>
                                    <span class="sm:hidden">Fri</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                    <span class="hidden sm:inline">Saturday</span>
                                    <span class="sm:hidden">Sat</span>
                                </div>
                                <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700">
                                    <span class="hidden sm:inline">Sunday</span>
                                    <span class="sm:hidden">Sun</span>
                                </div>
                            </div>

                            <!-- Dynamic Time slots grid -->
                            <div id="schedule-grid" class="divide-y divide-gray-200">
                                <?php
                                // Generate dynamic time slots from 7:00 AM to 9:00 PM in 30-minute intervals
                                $timeSlots = [];
                                $startHour = 7;
                                $endHour = 21;

                                for ($hour = $startHour; $hour < $endHour; $hour++) {
                                    for ($minute = 0; $minute < 60; $minute += 30) {
                                        $currentTime = sprintf('%02d:%02d', $hour, $minute);
                                        $nextTime = sprintf('%02d:%02d', $hour + ($minute + 30 >= 60 ? 1 : 0), ($minute + 30) % 60);

                                        // Skip if next time exceeds end hour
                                        if (($hour + ($minute + 30 >= 60 ? 1 : 0)) >= $endHour && ($minute + 30) % 60 > 0) {
                                            continue;
                                        }

                                        $timeSlots[] = [$currentTime, $nextTime];
                                    }
                                }

                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                                // Pre-process schedules for faster lookup
                                $scheduleLookup = [];
                                foreach ($schedules as $schedule) {
                                    $day = $schedule['day_of_week'];
                                    $start = substr($schedule['start_time'], 0, 5);
                                    $end = substr($schedule['end_time'], 0, 5);

                                    if (!isset($scheduleLookup[$day])) {
                                        $scheduleLookup[$day] = [];
                                    }

                                    $scheduleLookup[$day][] = [
                                        'schedule' => $schedule,
                                        'start' => $start,
                                        'end' => $end
                                    ];
                                }
                                ?>

                                <?php foreach ($timeSlots as $slotIndex => $time): ?>
                                    <?php
                                    $duration = strtotime($time[1]) - strtotime($time[0]);
                                    $rowSpan = max(1, $duration / 1800); // 30-minute base unit
                                    $minHeight = $rowSpan * 60; // Adjust height based on duration
                                    ?>
                                    <div class="grid grid-cols-8 min-h-[<?php echo $minHeight; ?>px] hover:bg-gray-50 transition-colors duration-200" style="grid-row: span <?php echo $rowSpan; ?>">
                                        <!-- Time Column -->
                                        <div class="px-3 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 sticky left-0 z-10 flex items-start" style="grid-row: span <?php echo $rowSpan; ?>">
                                            <span class="text-sm hidden sm:block"><?php echo date('g:i A', strtotime($time[0])) . ' - ' . date('g:i A', strtotime($time[1])); ?></span>
                                            <span class="text-xs sm:hidden"><?php echo date('g:i', strtotime($time[0])) . '-' . date('g:i', strtotime($time[1])); ?></span>
                                        </div>

                                        <!-- Day Columns -->
                                        <?php foreach ($days as $day): ?>
                                            <div class="px-1 py-1 border-r border-gray-200 last:border-r-0 relative drop-zone min-h-[<?php echo $minHeight; ?>px]"
                                                data-day="<?php echo $day; ?>"
                                                data-start-time="<?php echo $time[0]; ?>"
                                                data-end-time="<?php echo $time[1]; ?>">

                                                <?php
                                                $schedulesInSlot = [];
                                                if (isset($scheduleLookup[$day])) {
                                                    foreach ($scheduleLookup[$day] as $scheduleData) {
                                                        $scheduleStart = $scheduleData['start'];
                                                        $scheduleEnd = $scheduleData['end'];

                                                        $slotStart = strtotime("1970-01-01 " . $time[0] . ":00");
                                                        $slotEnd = strtotime("1970-01-01 " . $time[1] . ":00");
                                                        $schedStart = strtotime("1970-01-01 " . $scheduleStart . ":00");
                                                        $schedEnd = strtotime("1970-01-01 " . $scheduleEnd . ":00");

                                                        // Check if schedule overlaps with this time slot
                                                        if ($schedStart < $slotEnd && $schedEnd > $slotStart) {
                                                            $schedulesInSlot[] = [
                                                                'schedule' => $scheduleData['schedule'],
                                                                'isStartCell' => ($scheduleStart === $time[0]),
                                                                'isEndCell' => ($scheduleEnd === $time[1])
                                                            ];
                                                        }
                                                    }
                                                }
                                                ?>

                                                <?php if (empty($schedulesInSlot)): ?>
                                                    <button onclick="openAddModalForSlot('<?php echo $day; ?>', '<?php echo $time[0]; ?>', '<?php echo $time[1]; ?>')"
                                                        class="w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print flex items-center justify-center p-1 min-h-[<?php echo $minHeight; ?>px]">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <div class="space-y-1 p-1">
                                                        <?php foreach ($schedulesInSlot as $scheduleData):
                                                            $schedule = $scheduleData['schedule'];
                                                            $isStartCell = $scheduleData['isStartCell'];

                                                            $colors = [
                                                                'bg-blue-100 border-blue-300 text-blue-800',
                                                                'bg-green-100 border-green-300 text-green-800',
                                                                'bg-purple-100 border-purple-300 text-purple-800',
                                                                'bg-orange-100 border-orange-300 text-orange-800',
                                                                'bg-pink-100 border-pink-300 text-pink-800',
                                                                'bg-teal-100 border-teal-300 text-teal-800',
                                                                'bg-amber-100 border-amber-300 text-amber-800'
                                                            ];
                                                            // Use schedule_id for consistent coloring
                                                            $colorIndex = $schedule['schedule_id'] ? ($schedule['schedule_id'] % count($colors)) : array_rand($colors);
                                                            $colorClass = $colors[$colorIndex];
                                                        ?>
                                                            <div class="schedule-card <?php echo $colorClass; ?> p-2 rounded-lg border-l-4 draggable cursor-move text-xs"
                                                                draggable="true"
                                                                data-schedule-id="<?php echo $schedule['schedule_id']; ?>"
                                                                data-year-level="<?php echo htmlspecialchars($schedule['year_level']); ?>"
                                                                data-section-name="<?php echo htmlspecialchars($schedule['section_name']); ?>"
                                                                data-room-name="<?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>"
                                                                data-faculty-name="<?php echo htmlspecialchars($schedule['faculty_name']); ?>"
                                                                data-course-code="<?php echo htmlspecialchars($schedule['course_code']); ?>"
                                                                data-original-day="<?php echo htmlspecialchars($schedule['day_of_week']); ?>"
                                                                data-original-start="<?php echo substr($schedule['start_time'], 0, 5); ?>"
                                                                data-original-end="<?php echo substr($schedule['end_time'], 0, 5); ?>">
                                                                style="<?php echo !$isStartCell ? 'opacity: 0.6;' : ''; ?>">

                                                                <?php if ($isStartCell): ?>
                                                                    <div class="flex justify-between items-start mb-1">
                                                                        <div class="font-semibold truncate flex-1">
                                                                            <?php echo htmlspecialchars($schedule['course_code']); ?>
                                                                        </div>
                                                                        <div class="flex space-x-1 flex-shrink-0 ml-1">
                                                                            <button onclick="editSchedule('<?php echo $schedule['schedule_id']; ?>')" class="text-yellow-600 hover:text-yellow-700 no-print">
                                                                                <i class="fas fa-edit text-xs"></i>
                                                                                <button onclick="console.log('Delete clicked - Schedule ID:', <?php echo $schedule['schedule_id'] ?? 'null'; ?>); openDeleteSingleModal(
                                                                                        <?php echo $schedule['schedule_id'] ?? 'null'; ?>, 
                                                                                        '<?php echo htmlspecialchars($schedule['course_code'] ?? ''); ?>', 
                                                                                        '<?php echo htmlspecialchars($schedule['section_name'] ?? ''); ?>', 
                                                                                        '<?php echo htmlspecialchars($schedule['day_of_week'] ?? ''); ?>', 
                                                                                        '<?php echo isset($schedule['start_time']) ? date('g:i A', strtotime($schedule['start_time'])) : ''; ?>', 
                                                                                        '<?php echo isset($schedule['end_time']) ? date('g:i A', strtotime($schedule['end_time'])) : ''; ?>'
                                                                                    )" class="text-red-600 hover:text-red-700 no-print">
                                                                                    <i class="fas fa-trash text-xs"></i>
                                                                                </button>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="font-semibold truncate mb-1 text-center opacity-75">
                                                                        <i class="fas fa-ellipsis-h text-xs"></i>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if ($isStartCell): ?>
                                                                    <div class="opacity-90 truncate">
                                                                        <?php echo htmlspecialchars($schedule['section_name']); ?>
                                                                    </div>
                                                                    <div class="opacity-75 truncate">
                                                                        <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                                                    </div>
                                                                    <div class="opacity-75 truncate">
                                                                        <?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>
                                                                    </div>
                                                                    <div class="font-medium mt-1 hidden sm:block text-xs">
                                                                        <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIST VIEW -->
                <div id="list-view" class="hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Course Code</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Section</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Year Level</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Faculty</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Day</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Time</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Room</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200 schedule-row"
                                            data-schedule-id="<?php echo $schedule['schedule_id']; ?>"
                                            data-year-level="<?php echo htmlspecialchars($schedule['year_level'] ?? ''); ?>"
                                            data-section-name="<?php echo htmlspecialchars($schedule['section_name'] ?? ''); ?>"
                                            data-room-name="<?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>">
                                            <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                                <?php echo htmlspecialchars($schedule['course_code']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($schedule['section_name']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($schedule['year_level']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($schedule['day_of_week']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex space-x-2 justify-center">
                                                    <button onclick="editSchedule('<?php echo $schedule['schedule_id']; ?>')" class="text-yellow-600 hover:text-yellow-700 text-sm no-print">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="openDeleteSingleModal(
                                        '<?php echo $schedule['schedule_id']; ?>', 
                                        '<?php echo htmlspecialchars($schedule['course_code']); ?>', 
                                        '<?php echo htmlspecialchars($schedule['section_name']); ?>', 
                                        '<?php echo htmlspecialchars($schedule['day_of_week']); ?>', 
                                        '<?php echo date('g:i A', strtotime($schedule['start_time'])); ?>', 
                                        '<?php echo date('g:i A', strtotime($schedule['end_time'])); ?>'
                                    )" class="text-red-600 hover:text-red-700 text-sm no-print">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (empty($schedules)): ?>
                                <div class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p class="mt-2">No schedules found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Schedule Tab -->
        <div id="content-schedule" class="tab-content <?php echo $activeTab !== 'schedule-list' ? 'hidden' : ''; ?>">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <!-- Header Section -->
                <div class="mb-6">
                    <!-- Title Row -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                        <div class="flex items-center">
                            <div class="bg-yellow-500 p-2 rounded-lg mr-3">
                                <i class="fas fa-calendar text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Weekly Schedule View</h2>
                                <p class="text-sm text-gray-600 mt-1">View and filter all schedules</p>
                            </div>
                        </div>

                        <!-- Full Screen Buttons -->
                        <div class="flex items-center space-x-2">
                            <button id="fullscreen-view-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="toggleFullScreen('view')">
                                <i class="fas fa-expand mr-1"></i>
                                <span class="hidden sm:inline">Full Screen</span>
                            </button>
                            <button id="exit-fullscreen-view-btn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors hidden text-sm" onclick="toggleFullScreen('view')">
                                <i class="fas fa-compress mr-1"></i>
                                <span class="hidden sm:inline">Exit Full Screen</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filters Row -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-filter mr-2 text-yellow-600"></i>
                            Filter Schedules
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Year Level</label>
                                <select id="filter-year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm" onchange="filterSchedules()">
                                    <option value="">All Year Levels</option>
                                    <?php $yearLevels = array_unique(array_column($schedules, 'year_level')); ?>
                                    <?php foreach ($yearLevels as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                                <select id="filter-section" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm" onchange="filterSchedules()">
                                    <option value="">All Sections</option>
                                    <?php $sectionNames = array_unique(array_column($schedules, 'section_name')); ?>
                                    <?php foreach ($sectionNames as $section): ?>
                                        <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Room</label>
                                <select id="filter-room" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm" onchange="filterSchedules()">
                                    <option value="">All Rooms</option>
                                    <?php $rooms = array_unique(array_column($schedules, 'room_name')); ?>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo htmlspecialchars($room ?? 'Online'); ?>"><?php echo htmlspecialchars($room ?? 'Online'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="flex items-end space-x-2">
                                <button onclick="clearFilters()" class="flex-1 px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                                    <i class="fas fa-times mr-1"></i>
                                    Clear Filters
                                </button>
                            </div>

                            <div class="flex items-end">
                                <button id="delete-all-btn" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors text-sm" onclick="deleteAllSchedules()">
                                    <i class="fas fa-trash"></i>
                                    <span>Delete All</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Timetable -->
                <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="min-w-full">
                        <!-- Header with days -->
                        <div class="grid grid-cols-8 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                            <div class="px-3 py-3 text-sm font-semibold text-gray-700 border-r border-gray-200 bg-gray-50 sticky left-0 z-10">
                                <span class="hidden sm:inline">Time</span>
                                <span class="sm:hidden">⌚</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                <span class="hidden sm:inline">Monday</span>
                                <span class="sm:hidden">Mon</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                <span class="hidden sm:inline">Tuesday</span>
                                <span class="sm:hidden">Tue</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                <span class="hidden sm:inline">Wednesday</span>
                                <span class="sm:hidden">Wed</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                <span class="hidden sm:inline">Thursday</span>
                                <span class="sm:hidden">Thu</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                <span class="hidden sm:inline">Friday</span>
                                <span class="sm:hidden">Fri</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                <span class="hidden sm:inline">Saturday</span>
                                <span class="sm:hidden">Sat</span>
                            </div>
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700">
                                <span class="hidden sm:inline">Sunday</span>
                                <span class="sm:hidden">Sun</span>
                            </div>
                        </div>

                        <!-- Time slots -->
                        <div id="timetableGrid" class="divide-y divide-gray-200">
                            <?php
                            // Generate time slots from 7:00 AM to 9:00 PM in 30-minute intervals$scheduleGrid = [];
                            foreach ($schedules as $schedule) {
                                $day   = $schedule['day_of_week'];
                                $start = substr($schedule['start_time'], 0, 5);
                                if (!isset($scheduleGrid[$day]))         $scheduleGrid[$day]         = [];
                                if (!isset($scheduleGrid[$day][$start])) $scheduleGrid[$day][$start] = [];
                                $scheduleGrid[$day][$start][] = $schedule;
                            }
                            ?>
                            <?php foreach ($schedulesInSlot as $scheduleData):
                                $schedule    = $scheduleData['schedule'];
                                $isStartCell = $scheduleData['isStartCell'];

                                $colors = [
                                    'bg-blue-100 border-blue-300 text-blue-800',
                                    'bg-green-100 border-green-300 text-green-800',
                                    'bg-purple-100 border-purple-300 text-purple-800',
                                    'bg-orange-100 border-orange-300 text-orange-800',
                                    'bg-pink-100 border-pink-300 text-pink-800',
                                    'bg-teal-100 border-teal-300 text-teal-800',
                                    'bg-amber-100 border-amber-300 text-amber-800',
                                ];
                                $colorIndex = $schedule['schedule_id']
                                    ? ($schedule['schedule_id'] % count($colors))
                                    : array_rand($colors);
                                $colorClass = $colors[$colorIndex];

                                // Inline opacity style goes INSIDE the opening tag (Fix 3)
                                $cardStyle = !$isStartCell ? 'opacity:0.6;' : '';
                            ?>
                                <div class="schedule-card <?= $colorClass ?> p-2 rounded-lg border-l-4 draggable cursor-move text-xs"
                                    draggable="true"
                                    style="<?= $cardStyle ?>"
                                    data-schedule-id="<?= htmlspecialchars($schedule['schedule_id']) ?>"
                                    data-year-level="<?= htmlspecialchars($schedule['year_level']) ?>"
                                    data-section-name="<?= htmlspecialchars($schedule['section_name']) ?>"
                                    data-room-name="<?= htmlspecialchars($schedule['room_name'] ?? 'Online') ?>"
                                    data-faculty-name="<?= htmlspecialchars($schedule['faculty_name']) ?>"
                                    data-course-code="<?= htmlspecialchars($schedule['course_code']) ?>"
                                    data-original-day="<?= htmlspecialchars($schedule['day_of_week']) ?>"
                                    data-original-start="<?= substr($schedule['start_time'], 0, 5) ?>"
                                    data-original-end="<?= substr($schedule['end_time'], 0, 5) ?>">

                                    <?php if ($isStartCell): ?>
                                        <div class="flex justify-between items-start mb-1">
                                            <div class="font-semibold truncate flex-1">
                                                <?= htmlspecialchars($schedule['course_code']) ?>
                                            </div>
                                            <!-- FIX 2: edit and delete are SIBLING buttons, not nested -->
                                            <div class="flex space-x-1 flex-shrink-0 ml-1">
                                                <button onclick="editSchedule('<?= $schedule['schedule_id'] ?>')"
                                                    class="text-yellow-600 hover:text-yellow-700 no-print"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>
                                                <button onclick="openDeleteSingleModal(
                                        <?= (int)$schedule['schedule_id'] ?>,
                                        '<?= htmlspecialchars(addslashes($schedule['course_code'])) ?>',
                                        '<?= htmlspecialchars(addslashes($schedule['section_name'])) ?>',
                                        '<?= htmlspecialchars(addslashes($schedule['day_of_week'])) ?>',
                                        '<?= isset($schedule['start_time']) ? date('g:i A', strtotime($schedule['start_time'])) : '' ?>',
                                        '<?= isset($schedule['end_time'])   ? date('g:i A', strtotime($schedule['end_time']))   : '' ?>'
                                    )"
                                                    class="text-red-600 hover:text-red-700 no-print"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="opacity-90 truncate"><?= htmlspecialchars($schedule['section_name']) ?></div>
                                        <div class="opacity-75 truncate"><?= htmlspecialchars($schedule['faculty_name']) ?></div>
                                        <div class="opacity-75 truncate"><?= htmlspecialchars($schedule['room_name'] ?? 'Online') ?></div>
                                        <div class="font-medium mt-1 hidden sm:block text-xs">
                                            <?= date('g:i A', strtotime($schedule['start_time'])) ?>
                                            - <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="font-semibold truncate mb-1 text-center opacity-75">
                                            <i class="fas fa-ellipsis-h text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-30 backdrop-blur-md flex items-center justify-center z-50 hidden">
            <div class="bg-white p-8 rounded-lg shadow-xl text-center">
                <div class="pulsing-loader mx-auto mb-4"></div>
                <p class="text-gray-700 font-medium">Generating schedules...</p>
            </div>
        </div>

        <!-- Add/Edit Schedule Modal -->
        <div id="schedule-modal" class="fixed inset-0 bg-black bg-opacity-30 backdrop-blur-md items-center justify-center z-50 hidden modal-overlay">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 modal-content">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Add Schedule</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="schedule-form" class="space-y-4" onsubmit="handleScheduleSubmit(event)">
                    <input type="hidden" id="schedule-id" name="schedule_id">
                    <input type="hidden" id="modal-day" name="day_of_week">
                    <input type="hidden" id="modal-start-time" name="start_time">
                    <input type="hidden" id="modal-end-time" name="end_time">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Course Code</label>
                        <input type="text" id="course-code" name="course_code" list="course-codes"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                            required oninput="autoFillCourseName(this.value)">
                        <datalist id="course-codes">
                            <?php
                            $curriculumCourses = $jsData['curriculumCourses'] ?? [];
                            foreach ($curriculumCourses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_code']); ?>"
                                    data-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                                    data-year-level="<?php echo htmlspecialchars($course['curriculum_year']); ?>"
                                    data-course-id="<?php echo htmlspecialchars($course['course_id']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Course Name</label>
                        <input type="text" id="course-name" name="course_name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                            required readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Section</label>
                        <select id="section-name" name="section_name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change" required>
                            <option value="">Select Section</option>
                            <?php
                            // Group sections by year level for better organization
                            $sectionsByYear = [];
                            $sections = $jsData['sectionsData'] ?? [];

                            foreach ($sections as $section) {
                                $yearLevel = $section['year_level'] ?? 'Unknown';
                                if (!isset($sectionsByYear[$yearLevel])) {
                                    $sectionsByYear[$yearLevel] = [];
                                }
                                $sectionsByYear[$yearLevel][] = $section;
                            }

                            // Sort by year level
                            ksort($sectionsByYear);

                            foreach ($sectionsByYear as $yearLevel => $yearSections): ?>
                                <optgroup label="<?php echo htmlspecialchars($yearLevel); ?>">
                                    <?php foreach ($yearSections as $section): ?>
                                        <option value="<?php echo htmlspecialchars($section['section_name']); ?>"
                                            data-year-level="<?php echo htmlspecialchars($section['year_level']); ?>"
                                            data-max-students="<?php echo htmlspecialchars($section['max_students']); ?>"
                                            data-current-students="<?php echo htmlspecialchars($section['current_students']); ?>">
                                            <?php echo htmlspecialchars($section['section_name']); ?>
                                            (<?php echo htmlspecialchars($section['current_students']); ?>/<?php echo htmlspecialchars($section['max_students']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <!-- Section details display area -->
                        <div id="section-details" class="mt-2 hidden"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Faculty</label>
                        <select id="faculty-name" name="faculty_name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change" required>
                            <option value="">Select Faculty</option>
                            <?php foreach ($faculty as $fac): ?>
                                <option value="<?php echo htmlspecialchars($fac['name']); ?>">
                                    <?php echo htmlspecialchars($fac['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Room</label>
                        <select id="room-name" name="room_name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change" required>
                            <option value="Online">Online</option>
                            <?php foreach ($classrooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_name']); ?>">
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time</label>
                            <select id="start-time" name="start_time_display"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change"
                                onchange="updateEndTimeOptions(); updateTimeFields()" required>
                                <option value="">Select Start Time</option>
                                <?php
                                // Generate time options from 7:00 AM to 9:00 PM in 30-minute intervals
                                for ($hour = 7; $hour < 22; $hour++) {
                                    for ($minute = 0; $minute < 60; $minute += 30) {
                                        $timeValue = sprintf('%02d:%02d', $hour, $minute);
                                        $timeDisplay = date('g:i A', strtotime($timeValue));
                                        echo "<option value=\"$timeValue\">$timeDisplay</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Time</label>
                            <select id="end-time" name="end_time_display"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change"
                                onchange="updateTimeFields()" required>
                                <option value="">Select End Time</option>
                                <!-- End times will be populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Day Pattern</label>
                        <select id="day-select" name="day_select_display"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                            onchange="updateDayField()">
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                            <option value="MWF">MWF (Mon, Wed, Fri)</option>
                            <option value="TTH">TTH (Tue, Thu)</option>
                            <option value="MW">MW (Mon, Wed)</option>
                            <option value="TTHS">TTHS (Tue, Thu, Sat)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Schedule Type</label>
                        <select id="schedule-type" name="schedule_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="f2f">Face to Face (F2F)</option>
                            <option value="online">Online</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="delete-confirmation-modal" class="fixed inset-0 bg-opacity-50 backdrop-blur-sm hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-2 rounded-full mr-3">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Delete All Schedules</h3>
                    </div>
                    <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mb-6">
                    <p class="text-gray-700 mb-4">
                        Are you sure you want to delete <strong>ALL schedules</strong> for your department? This action cannot be undone.
                    </p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                            <span class="text-sm font-medium text-yellow-800">This will permanently remove all generated schedules for the current semester.</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDeleteAllSchedules()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete All Schedules
                    </button>
                </div>
            </div>
        </div>

        <!-- Single Delete Confirmation Modal -->
        <div id="delete-single-modal" class="fixed inset-0 bg-opacity-50 backdrop-blur-sm hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-2 rounded-full mr-3">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Delete Schedule</h3>
                    </div>
                    <button type="button" onclick="closeDeleteSingleModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mb-6">
                    <p class="text-gray-700 mb-4">
                        Are you sure you want to delete this schedule? This action cannot be undone.
                    </p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                            <span class="text-sm font-medium text-yellow-800" id="single-delete-details">
                                This schedule will be permanently removed.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeDeleteSingleModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDeleteSingleSchedule()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Schedule
                    </button>
                </div>
            </div>
        </div>

        <!-- Regenerate Confirmation Modal -->
        <div id="regenerate-confirmation-modal" class="fixed inset-0 bg-black/70 opacity-30 backdrop-blur-sm hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 transform transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-full mr-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Regenerate Schedules?</h3>
                    </div>
                    <button type="button" onclick="closeRegenerateModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mb-6">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-semibold text-yellow-800 mb-2">
                                    Warning: This action will affect existing schedules
                                </p>
                                <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                                    <li>All previously generated schedules will be <strong>replaced</strong></li>
                                    <li>Any manual edits will be <strong>lost</strong></li>
                                    <li>Faculty, room, and time assignments will be <strong>recalculated</strong></li>
                                    <li>This action <strong>cannot be undone</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-lightbulb text-blue-600 mr-2 mt-1"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">Recommendation:</p>
                                <p>Consider using the <strong>Manual Edit</strong> tab to make adjustments to existing schedules instead of regenerating completely.</p>
                            </div>
                        </div>
                    </div>

                    <div id="existing-schedules-info" class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm font-medium text-gray-700 mb-2">Current Schedule Statistics:</p>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-book mr-2 text-blue-500"></i>
                                <span><strong id="current-courses-count">0</strong> Courses</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-users mr-2 text-green-500"></i>
                                <span><strong id="current-sections-count">0</strong> Sections</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-chalkboard-teacher mr-2 text-purple-500"></i>
                                <span><strong id="current-faculty-count">0</strong> Faculty</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-door-open mr-2 text-orange-500"></i>
                                <span><strong id="current-rooms-count">0</strong> Rooms</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeRegenerateModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" onclick="confirmRegenerate()"
                        class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors font-semibold">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Yes, Regenerate Schedules
                    </button>
                </div>
            </div>
        </div>

        <script>
            // ── Global data injected from PHP ──────────────────────────────────────────
            window.scheduleData = <?= json_encode($schedules,         JSON_UNESCAPED_UNICODE) ?> || [];
            window.jsData = <?= json_encode($jsData ?? [],      JSON_UNESCAPED_UNICODE) ?>;
            window.departmentId = window.jsData.departmentId || null;
            window.currentSemester = window.jsData.currentSemester || {};
            window.rawSectionsData = window.jsData.sectionsData || [];
            window.currentAcademicYear = window.jsData.currentAcademicYear || "";
            window.faculty = window.jsData.faculty || [];
            window.classrooms = window.jsData.classrooms || [];
            window.curricula = window.jsData.curricula || [];
            window.curriculumCourses = window.jsData.curriculumCourses || [];

            // ── Authoritative switchTab (only defined ONCE here) ───────────────────────
            window.switchTab = function(tabName) {
                // Exit full screen if active
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.style.display === 'none') {
                    // determine which tab was fullscreened and exit
                    ['manual', 'view'].forEach(t => {
                        const btn = document.getElementById(`fullscreen-${t}-btn`);
                        if (btn && !btn.classList.contains('hidden')) toggleFullScreen(t);
                    });
                    setTimeout(() => _performTabSwitch(tabName), 60);
                    return;
                }
                _performTabSwitch(tabName);
            };

            function _performTabSwitch(tabName) {
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('bg-yellow-500', 'text-white');
                    btn.classList.add('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
                });

                const activeBtn = document.getElementById(`tab-${tabName}`);
                if (activeBtn) {
                    activeBtn.classList.add('bg-yellow-500', 'text-white');
                    activeBtn.classList.remove('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
                }

                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));

                const activeContent = document.getElementById(`content-${tabName}`);
                if (activeContent) activeContent.classList.remove('hidden');

                const url = new URL(window.location);
                url.searchParams.set('tab', tabName === 'schedule' ? 'schedule-list' : tabName);
                window.history.pushState({}, '', url);

                // Refresh grids when switching to manual or view tab
                if ((tabName === 'manual' || tabName === 'schedule') && window.scheduleData?.length) {
                    requestAnimationFrame(() => {
                        setTimeout(() => {
                            if (typeof window.safeUpdateScheduleDisplay === 'function') {
                                window.safeUpdateScheduleDisplay(window.scheduleData);
                            }
                            if (tabName === 'manual' && typeof initializeDragAndDrop === 'function') {
                                setTimeout(initializeDragAndDrop, 100);
                            }
                        }, 60);
                    });
                }
            }

            // ── Full screen toggle ─────────────────────────────────────────────────────
            function toggleFullScreen(tab) {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.querySelector('.main-content');
                const header = document.querySelector('header');
                const fullBtn = document.getElementById(`fullscreen-${tab}-btn`);
                const exitBtn = document.getElementById(`exit-fullscreen-${tab}-btn`);
                const isFullScreen = sidebar.style.display === 'none';

                if (!isFullScreen) {
                    sidebar.style.display = 'none';
                    header.style.display = 'none';
                    mainContent.style.marginLeft = '0';
                    mainContent.style.padding = '0';
                    mainContent.style.cssText += ';position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:1000;background:white;overflow:auto;';
                    document.body.style.overflow = 'hidden';
                    fullBtn?.classList.add('hidden');
                    exitBtn?.classList.remove('hidden');
                } else {
                    ['display', 'marginLeft', 'padding', 'position', 'top', 'left', 'width', 'height', 'zIndex', 'background', 'overflow'].forEach(p => mainContent.style[p] = '');
                    sidebar.style.display = '';
                    header.style.display = '';
                    document.body.style.overflow = '';
                    fullBtn?.classList.remove('hidden');
                    exitBtn?.classList.add('hidden');
                }
            }

            // ── Print / Export ──────────────────────────────────────────────────────────
            function togglePrintDropdown() {
                document.getElementById('printDropdown').classList.toggle('hidden');
            }

            function exportSchedule(type) {
                document.getElementById("printDropdown").classList.add("hidden");

                if (type === 'excel') {
                    exportToExcel();
                } else if (type === 'pdf') {
                    exportToPDF();
                }
            }

            // Add this helper function that's used by both export functions
            function getFilteredSchedules() {
                const yearLevel = document.getElementById('filter-year')?.value || '';
                const section = document.getElementById('filter-section')?.value || '';
                const room = document.getElementById('filter-room')?.value || '';

                return window.scheduleData.filter(schedule => {
                    const matchesYear = !yearLevel || schedule.year_level === yearLevel;
                    const matchesSection = !section || schedule.section_name === section;
                    const matchesRoom = !room || (schedule.room_name || 'Online') === room;

                    return matchesYear && matchesSection && matchesRoom;
                });
            }

            function printSchedule(type) {
                document.getElementById("printDropdown").classList.add("hidden");

                // Apply filters if needed
                if (type === "filtered") {
                    filterSchedules();
                } else if (type === "all") {
                    clearFilters();
                }

                // Switch to schedule view tab and wait for render
                switchTab("schedule");

                setTimeout(() => {
                    createPrintVersion();
                }, 500);
            }

            function getFilteredSchedules() {
                const yearLevel = document.getElementById('filter-year')?.value || '';
                const section = document.getElementById('filter-section')?.value || '';
                const room = document.getElementById('filter-room')?.value || '';

                return window.scheduleData.filter(schedule => {
                    const matchesYear = !yearLevel || schedule.year_level === yearLevel;
                    const matchesSection = !section || schedule.section_name === section;
                    const matchesRoom = !room || (schedule.room_name || 'Online') === room;

                    return matchesYear && matchesSection && matchesRoom;
                });
            }

            function createPrintVersion() {
                // Create a new window for printing
                const printWindow = window.open("", "_blank");
                if (!printWindow) {
                    alert("Please allow popups for printing");
                    return;
                }

                // Get semester information
                const semester = window.currentSemester?.semester_name || "";
                const academicYear = window.currentSemester?.academic_year || "";
                const currentDate = new Date().toLocaleDateString();

                // Create the print content
                const printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Academic Schedule - ${semester} ${academicYear}</title>
                        <style>
                    body {
                        font-family: "Times New Roman", serif;
                        margin: 0;
                        padding: 20px;
                        font-size: 12pt;
                        line-height: 1.2;
                    }
                    
                    .university-header {
                        text-align: center;
                        border-bottom: 2px solid #000;
                        padding-bottom: 15px;
                        margin-bottom: 20px;
                    }
                    
                    .header-content {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 10px;
                        gap: 20px;
                    }
                    
                    .university-info {
                        flex: 1;
                        text-align: center;
                    }
                    
                    .university-name {
                        font-size: 14pt;
                        font-weight: bold;
                        margin: 0;
                    }
                    
                    .university-subtitle {
                        font-size: 10pt;
                        font-style: italic;
                        margin: 2px 0;
                    }
                    
                    .logo-container {
                        width: 80px;
                        text-align: center;
                    }
                    
                    .university-logo, .college-logo {
                        max-width: 60px;
                        max-height: 60px;
                        object-fit: contain;
                    }
                    
                    .semester-info {
                        font-size: 12pt;
                        font-weight: bold;
                        margin: 5px 0;
                    }
                    
                    .academic-year {
                        font-size: 11pt;
                        font-weight: bold;
                    }
                    
                    .schedule-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 9pt;
                        margin-top: 15px;
                    }
                    
                    .schedule-table th,
                    .schedule-table td {
                        border: 1px solid #000;
                        padding: 4px 6px;
                        text-align: center;
                        vertical-align: top;
                    }
                    
                    .schedule-table th {
                        background-color: #f0f0f0;
                        font-weight: bold;
                    }
                    
                    .time-column { width: 12%; }
                    .days-column { width: 8%; }
                    .course-column { width: 25%; }
                    .units-column { width: 12%; }
                    .room-column { width: 10%; }
                    .section-column { width: 15%; }
                    .students-column { width: 8%; }
                    .faculty-column { width: 10%; }
                    
                    .faculty-section {
                        margin-bottom: 25px;
                        page-break-inside: avoid;
                    }
                    
                    .faculty-name {
                        font-weight: bold;
                        font-size: 11pt;
                        margin-bottom: 8px;
                        background-color: #e0e0e0;
                        padding: 5px 10px;
                    }
                    
                    @media print {
                        body { margin: 0.5in; }
                        .faculty-section { page-break-inside: avoid; }
                        .logo-container img { max-width: 60px !important; }
                    }
                </style>
                    </head>
                    <body>
                        <div class="university-header">
                            <div class="header-content">
                                <div class="logo-container">
                                    <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="University Logo" class="university-logo">
                                </div>
                                <div class="university-info">
                                    <div class="university-name">Republic of the Philippines</div>
                                    <div class="university-name">President Ramon Magsaysay State University</div>
                                    <div class="university-subtitle">(formerly Ramon Magsaysay Technological University)</div>
                                </div>
                                <div class="logo-container">
                                    <img src="${window.location.origin}<?php echo $collegeLogoPath; ?>" alt="College Logo" class="college-logo" onerror="this.style.display='none'; console.log('College logo not found')">
                                </div>
                            </div>
                                    <div class="semester-info">${semester} Semester</div>
                                    <div class="academic-year">Academic Year ${academicYear}</div>
                                    <div style="font-size: 10pt; margin-top: 5px;">Generated on: ${currentDate}</div>
                                </div>
                                
                                ${generatePrintScheduleTable()}
                            </body>
                            </html>
                        `;

                printWindow.document.write(printContent);
                printWindow.document.close();

                // Wait for content to load then print
                printWindow.onload = function() {
                    printWindow.print();
                    // printWindow.close(); // Uncomment if you want to auto-close after printing
                };
            }

            function generatePrintScheduleTable() {
                if (!window.scheduleData || window.scheduleData.length === 0) {
                    return '<div style="text-align: center; padding: 20px; font-style: italic;">No schedule data available</div>';
                }

                // Organize schedules by faculty
                const facultySchedules = organizeSchedulesByFaculty(window.scheduleData);

                let tableHTML = "";

                Object.keys(facultySchedules)
                    .sort()
                    .forEach((facultyName) => {
                        const schedules = facultySchedules[facultyName];

                        tableHTML += `
                            <div class="faculty-section">
                                <div class="faculty-name">${facultyName}</div>
                                <table class="schedule-table">
                                    <thead>
                                        <tr>
                                            <th class="time-column">Time</th>
                                            <th class="days-column">Days</th>
                                            <th class="course-column">Course Code and Title</th>
                                            
                                            <th class="room-column">Room</th>
                                            <th class="section-column">Year/Section</th>
                                            <th class="students-column">No. of Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        // Group schedules by course and time for better display
                        const groupedSchedules = groupSchedulesForDisplay(schedules);

                        groupedSchedules.forEach((schedule) => {
                            tableHTML += `
                <tr>
                    <td class="time-column">${formatTimeForPrint(
                      schedule.startTime
                    )} - ${formatTimeForPrint(schedule.endTime)}</td>
                    <td class="days-column">${schedule.dayPattern}</td>
                    <td class="course-column" style="text-align: left;">
                        <div style="font-weight: bold;">${
                          schedule.courseCode
                        }</div>
                        <div style="font-size: 8pt;">${
                          schedule.courseName || ""
                        }</div>
                    </td>
                    
                    <td class="room-column">${schedule.room}</td>
                    <td class="section-column">${schedule.section}</td>
                    <td class="students-column">${schedule.students || ""}</td>
                </tr>
                    `;
                        });

                        tableHTML += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    });

                return tableHTML;
            }

            function formatTimeForPrint(timeString) {
                if (!timeString) return "";
                const time = timeString.substring(0, 5);
                const [hours, minutes] = time.split(":");
                const date = new Date(2000, 0, 1, hours, minutes);
                return date.toLocaleTimeString("en-US", {
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true,
                });
            }

            // Helper function to organize schedules by faculty
            function organizeSchedulesByFaculty(schedules) {
                const facultySchedules = {};

                schedules.forEach((schedule) => {
                    const facultyName = schedule.faculty_name || "Unassigned";

                    if (!facultySchedules[facultyName]) {
                        facultySchedules[facultyName] = [];
                    }

                    facultySchedules[facultyName].push(schedule);
                });

                return facultySchedules;
            }

            // Helper function to group schedules for display
            function groupSchedulesForDisplay(schedules) {
                const groups = {};

                schedules.forEach((schedule) => {
                    const key = `${schedule.course_code}-${schedule.start_time}-${schedule.end_time}-${schedule.faculty_name}`;

                    if (!groups[key]) {
                        groups[key] = {
                            courseCode: schedule.course_code,
                            courseName: schedule.course_name || "",
                            startTime: schedule.start_time,
                            endTime: schedule.end_time,
                            faculty: schedule.faculty_name,
                            room: schedule.room_name || "Online",
                            section: schedule.section_name || "",
                            students: schedule.current_students || "",
                            lectureUnits: schedule.lecture_units || "0",
                            labUnits: schedule.lab_units || "0",
                            days: [],
                        };
                    }

                    groups[key].days.push(schedule.day_of_week);
                });

                // Convert to array and format day patterns
                return Object.values(groups)
                    .map((group) => {
                        group.dayPattern = getDayPatternDisplay(group.days);
                        return group;
                    })
                    .sort((a, b) => a.startTime.localeCompare(b.startTime));
            }

            function getDayPatternDisplay(days) {
                const dayMap = {
                    Monday: "M",
                    Tuesday: "T",
                    Wednesday: "W",
                    Thursday: "TH",
                    Friday: "F",
                    Saturday: "S",
                    Sunday: "SU",
                };

                const pattern = days.map((day) => dayMap[day] || day).join("");

                // Common patterns
                const commonPatterns = {
                    MWF: "MWF",
                    TTH: "TTH",
                    MW: "MW",
                    TTHS: "TTHS",
                    MTWTHF: "Daily",
                };

                return commonPatterns[pattern] || pattern;
            }

            function exportToExcel() {
                // Get filtered schedules
                const filteredSchedules = getFilteredSchedules();

                if (filteredSchedules.length === 0) {
                    showNotification('No schedules to export.', 'error');
                    return;
                }

                // Create workbook
                const workbook = XLSX.utils.book_new();

                // Create main data sheet with ALL schedules
                const mainData = [
                    // Header row
                    ['Faculty', 'Time', 'Days', 'Course Code', 'Course Name', 'Section', 'Room', 'Students', 'Lecture Units', 'Lab Units']
                ];

                // Add all schedules to main sheet
                filteredSchedules.forEach(schedule => {
                    mainData.push([
                        schedule.faculty_name || 'Unassigned',
                        `${formatTime(schedule.start_time?.substring(0, 5) || '')} - ${formatTime(schedule.end_time?.substring(0, 5) || '')}`,
                        schedule.day_of_week || '',
                        schedule.course_code || '',
                        schedule.course_name || '',
                        schedule.section_name || '',
                        schedule.room_name || 'Online',
                        schedule.current_students || '',
                        schedule.lecture_units || '0',
                        schedule.lab_units || '0'
                    ]);
                });

                // Create main worksheet
                const mainWorksheet = XLSX.utils.aoa_to_sheet(mainData);

                // Set column widths for main sheet
                mainWorksheet['!cols'] = [{
                        wch: 20
                    }, // Faculty
                    {
                        wch: 15
                    }, // Time
                    {
                        wch: 8
                    }, // Days
                    {
                        wch: 12
                    }, // Course Code
                    {
                        wch: 30
                    }, // Course Name
                    {
                        wch: 15
                    }, // Section
                    {
                        wch: 12
                    }, // Room
                    {
                        wch: 10
                    }, // Students
                    {
                        wch: 12
                    }, // Lecture Units
                    {
                        wch: 10
                    } // Lab Units
                ];

                XLSX.utils.book_append_sheet(workbook, mainWorksheet, 'All Schedules');

                // Generate Excel file
                const semester = window.currentSemester?.semester_name || 'Unknown';
                const academicYear = window.currentSemester?.academic_year || 'Unknown';
                const fileName = `Schedule_${semester}_${academicYear}_${new Date().toISOString().split('T')[0]}.xlsx`;

                XLSX.writeFile(workbook, fileName);
                showNotification('Excel file exported successfully!', 'success');
            }

            function exportToPDF() {
                const {
                    jsPDF
                } = window.jspdf;

                // Get filtered schedules
                const filteredSchedules = getFilteredSchedules();

                if (filteredSchedules.length === 0) {
                    showNotification('No schedules to export.', 'error');
                    return;
                }

                // Create new PDF document
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });

                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const margin = 10;
                const contentWidth = pageWidth - (2 * margin);

                // Add header with university info
                addPDFHeader(doc, pageWidth, margin);

                let currentY = 60; // Start below header

                // Group schedules by faculty
                const facultyGroups = {};
                filteredSchedules.forEach(schedule => {
                    const facultyName = schedule.faculty_name || 'Unassigned';
                    if (!facultyGroups[facultyName]) {
                        facultyGroups[facultyName] = [];
                    }
                    facultyGroups[facultyName].push(schedule);
                });

                // Add content for each faculty
                Object.keys(facultyGroups).sort().forEach((facultyName, index) => {
                    // Check if we need a new page
                    if (currentY > pageHeight - 40 && index > 0) {
                        doc.addPage();
                        currentY = margin + 20;
                        addPDFHeader(doc, pageWidth, margin, true);
                    }

                    // Add faculty section header
                    doc.setFillColor(200, 200, 200);
                    doc.rect(margin, currentY, contentWidth, 8, 'F');
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text(`Faculty: ${facultyName}`, margin + 5, currentY + 5);

                    currentY += 15;

                    // Create table for this faculty
                    const schedules = facultyGroups[facultyName];
                    addScheduleTable(doc, schedules, margin, currentY, contentWidth);

                    currentY += (schedules.length * 8) + 20;
                });

                // Save PDF
                const semester = window.currentSemester?.semester_name || 'Unknown';
                const academicYear = window.currentSemester?.academic_year || 'Unknown';
                const fileName = `Schedule_${semester}_${academicYear}_${new Date().toISOString().split('T')[0]}.pdf`;

                doc.save(fileName);
                showNotification('PDF file exported successfully!', 'success');
            }

            function addPDFHeader(doc, pageWidth, margin, isSubsequentPage = false) {
                const centerX = pageWidth / 2;

                // University header text
                doc.setFontSize(16);
                doc.setFont(undefined, 'bold');
                doc.text('Republic of the Philippines', centerX, 15, {
                    align: 'center'
                });
                doc.text('President Ramon Magsaysay State University', centerX, 22, {
                    align: 'center'
                });

                doc.setFontSize(10);
                doc.setFont(undefined, 'normal');
                doc.text('(formerly Ramon Magsaysay Technological University)', centerX, 27, {
                    align: 'center'
                });

                // Semester info
                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                const semester = window.currentSemester?.semester_name || 'Unknown';
                const academicYear = window.currentSemester?.academic_year || 'Unknown';
                doc.text(`${semester} Semester - Academic Year ${academicYear}`, centerX, 37, {
                    align: 'center'
                });

                if (!isSubsequentPage) {
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    doc.text(`Generated on: ${new Date().toLocaleDateString()}`, centerX, 44, {
                        align: 'center'
                    });
                }

                // Add line separator
                doc.setDrawColor(0, 0, 0);
                doc.line(margin, 48, pageWidth - margin, 48);
            }

            function addScheduleTable(doc, schedules, startX, startY, tableWidth) {
                const colWidths = [
                    tableWidth * 0.15, // Time
                    tableWidth * 0.08, // Days
                    tableWidth * 0.12, // Course Code
                    tableWidth * 0.25, // Course Name
                    tableWidth * 0.15, // Section
                    tableWidth * 0.12, // Room
                    tableWidth * 0.08, // Students
                    tableWidth * 0.05 // Units
                ];

                // Table headers
                const headers = ['Time', 'Days', 'Course Code', 'Course Name', 'Section', 'Room', 'Students', 'Units'];

                let currentX = startX;
                let currentY = startY;

                // Draw header row
                doc.setFillColor(100, 100, 100);
                doc.rect(currentX, currentY, tableWidth, 8, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');

                headers.forEach((header, index) => {
                    doc.text(header, currentX + 2, currentY + 5);
                    currentX += colWidths[index];
                });

                currentY += 8;
                doc.setTextColor(0, 0, 0);
                doc.setFont(undefined, 'normal');

                // Draw schedule rows
                schedules.forEach((schedule, rowIndex) => {
                    currentX = startX;

                    // Alternate row colors
                    if (rowIndex % 2 === 0) {
                        doc.setFillColor(240, 240, 240);
                        doc.rect(currentX, currentY, tableWidth, 8, 'F');
                    }

                    const rowData = [
                        `${formatTime(schedule.start_time?.substring(0, 5) || '')} - ${formatTime(schedule.end_time?.substring(0, 5) || '')}`,
                        schedule.day_of_week || '',
                        schedule.course_code || '',
                        schedule.course_name || '',
                        schedule.section_name || '',
                        schedule.room_name || 'Online',
                        schedule.current_students?.toString() || '',
                        `${schedule.lecture_units || '0'}/${schedule.lab_units || '0'}`
                    ];

                    rowData.forEach((cell, colIndex) => {
                        doc.text(cell.substring(0, 20), currentX + 2, currentY + 5); // Limit text length
                        currentX += colWidths[colIndex];
                    });

                    currentY += 8;

                    // Check if we need a new page
                    if (currentY > doc.internal.pageSize.getHeight() - 20) {
                        doc.addPage();
                        currentY = 20;
                        addPDFHeader(doc, doc.internal.pageSize.getWidth(), 10, true);
                    }
                });
            }

            function exportToPDF() {
                const {
                    jsPDF
                } = window.jspdf;

                // Get filtered schedules
                const filteredSchedules = getFilteredSchedules();

                if (filteredSchedules.length === 0) {
                    showNotification('No schedules to export.', 'error');
                    return;
                }

                // Create new PDF document
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });

                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const margin = 10;
                const contentWidth = pageWidth - (2 * margin);

                // Add header with university info
                addPDFHeader(doc, pageWidth, margin);

                let currentY = 60; // Start below header

                // Group schedules by faculty
                const facultyGroups = {};
                filteredSchedules.forEach(schedule => {
                    const facultyName = schedule.faculty_name || 'Unassigned';
                    if (!facultyGroups[facultyName]) {
                        facultyGroups[facultyName] = [];
                    }
                    facultyGroups[facultyName].push(schedule);
                });

                // Add content for each faculty
                Object.keys(facultyGroups).sort().forEach((facultyName, index) => {
                    // Check if we need a new page
                    if (currentY > pageHeight - 40 && index > 0) {
                        doc.addPage();
                        currentY = margin + 20;
                        addPDFHeader(doc, pageWidth, margin, true);
                    }

                    // Add faculty section header
                    doc.setFillColor(200, 200, 200);
                    doc.rect(margin, currentY, contentWidth, 8, 'F');
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text(`Faculty: ${facultyName}`, margin + 5, currentY + 5);

                    currentY += 15;

                    // Create table for this faculty
                    const schedules = facultyGroups[facultyName];
                    addScheduleTable(doc, schedules, margin, currentY, contentWidth);

                    currentY += (schedules.length * 8) + 20;
                });

                // Save PDF
                const semester = window.currentSemester?.semester_name || 'Unknown';
                const academicYear = window.currentSemester?.academic_year || 'Unknown';
                const fileName = `Schedule_${semester}_${academicYear}_${new Date().toISOString().split('T')[0]}.pdf`;

                doc.save(fileName);
                showNotification('PDF file exported successfully!', 'success');
            }

            function addPDFHeader(doc, pageWidth, margin, isSubsequentPage = false) {
                const centerX = pageWidth / 2;

                // University header text
                doc.setFontSize(16);
                doc.setFont(undefined, 'bold');
                doc.text('Republic of the Philippines', centerX, 15, {
                    align: 'center'
                });
                doc.text('President Ramon Magsaysay State University', centerX, 22, {
                    align: 'center'
                });

                doc.setFontSize(10);
                doc.setFont(undefined, 'normal');
                doc.text('(formerly Ramon Magsaysay Technological University)', centerX, 27, {
                    align: 'center'
                });

                // Semester info
                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                const semester = window.currentSemester?.semester_name || 'Unknown';
                const academicYear = window.currentSemester?.academic_year || 'Unknown';
                doc.text(`${semester} Semester - Academic Year ${academicYear}`, centerX, 37, {
                    align: 'center'
                });

                if (!isSubsequentPage) {
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    doc.text(`Generated on: ${new Date().toLocaleDateString()}`, centerX, 44, {
                        align: 'center'
                    });
                }

                // Add line separator
                doc.setDrawColor(0, 0, 0);
                doc.line(margin, 48, pageWidth - margin, 48);
            }

            function addScheduleTable(doc, schedules, startX, startY, tableWidth) {
                const colWidths = [
                    tableWidth * 0.15, // Time
                    tableWidth * 0.08, // Days
                    tableWidth * 0.12, // Course Code
                    tableWidth * 0.25, // Course Name
                    tableWidth * 0.15, // Section
                    tableWidth * 0.12, // Room
                    tableWidth * 0.08, // Students
                    tableWidth * 0.05 // Units
                ];

                // Table headers
                const headers = ['Time', 'Days', 'Course Code', 'Course Name', 'Section', 'Room', 'Students', 'Units'];

                let currentX = startX;
                let currentY = startY;

                // Draw header row
                doc.setFillColor(100, 100, 100);
                doc.rect(currentX, currentY, tableWidth, 8, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');

                headers.forEach((header, index) => {
                    doc.text(header, currentX + 2, currentY + 5);
                    currentX += colWidths[index];
                });

                currentY += 8;
                doc.setTextColor(0, 0, 0);
                doc.setFont(undefined, 'normal');

                // Draw schedule rows
                schedules.forEach((schedule, rowIndex) => {
                    currentX = startX;

                    // Alternate row colors
                    if (rowIndex % 2 === 0) {
                        doc.setFillColor(240, 240, 240);
                        doc.rect(currentX, currentY, tableWidth, 8, 'F');
                    }

                    const rowData = [
                        `${formatTime(schedule.start_time?.substring(0, 5) || '')} - ${formatTime(schedule.end_time?.substring(0, 5) || '')}`,
                        schedule.day_of_week || '',
                        schedule.course_code || '',
                        schedule.course_name || '',
                        schedule.section_name || '',
                        schedule.room_name || 'Online',
                        schedule.current_students?.toString() || '',
                        `${schedule.lecture_units || '0'}/${schedule.lab_units || '0'}`
                    ];

                    rowData.forEach((cell, colIndex) => {
                        doc.text(cell.substring(0, 20), currentX + 2, currentY + 5); // Limit text length
                        currentX += colWidths[colIndex];
                    });

                    currentY += 8;

                    // Check if we need a new page
                    if (currentY > doc.internal.pageSize.getHeight() - 20) {
                        doc.addPage();
                        currentY = 20;
                        addPDFHeader(doc, doc.internal.pageSize.getWidth(), 10, true);
                    }
                });
            }

            // ── View-tab filter functions ───────────────────────────────────────────────
            function filterSchedules() {
                const year = document.getElementById('filter-year')?.value || '';
                const section = document.getElementById('filter-section')?.value || '';
                const room = document.getElementById('filter-room')?.value || '';

                document.querySelectorAll('#timetableGrid .schedule-item').forEach(item => {
                    const matches =
                        (!year || item.dataset.yearLevel === year) &&
                        (!section || item.dataset.sectionName === section) &&
                        (!room || item.dataset.roomName === room);
                    item.style.display = matches ? '' : 'none';
                });
            }

            function clearFilters() {
                ['filter-year', 'filter-section', 'filter-room'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                filterSchedules();
            }

            // ── Misc UI helpers ─────────────────────────────────────────────────────────
            function formatTime(time) {
                const [h, m] = time.split(':');
                return new Date(2000, 0, 1, h, m).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            function escapeHtml(unsafe) {
                if (!unsafe) return '';
                return String(unsafe)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            function showNotification(message, type = 'success') {
                const container = document.getElementById('toast-container') || (() => {
                    const c = document.createElement('div');
                    c.id = 'toast-container';
                    c.className = 'fixed top-4 right-4 z-50 space-y-2';
                    document.body.appendChild(c);
                    return c;
                })();
                const colors = {
                    success: 'green',
                    error: 'red',
                    warning: 'yellow'
                };
                const color = colors[type] || 'blue';
                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    warning: 'fa-exclamation-triangle'
                };
                const toast = document.createElement('div');
                toast.className = `bg-${color}-50 border border-${color}-200 rounded-lg p-4 shadow-lg max-w-sm w-full transition-opacity duration-300`;
                toast.innerHTML = `
      <div class="flex items-start">
        <i class="fas ${icons[type] || 'fa-info-circle'} text-${color}-500 text-lg flex-shrink-0"></i>
        <p class="ml-3 text-sm text-${color}-800 flex-1 whitespace-pre-line">${escapeHtml(message)}</p>
        <button onclick="this.closest('.transition-opacity').remove()" class="ml-3 text-${color}-400 hover:text-${color}-600 flex-shrink-0">
          <i class="fas fa-times"></i>
        </button>
      </div>`;
                container.appendChild(toast);
                setTimeout(() => {
                    toast.classList.add('opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // ── Tab state management (event-driven, no setInterval) ────────────────────
            function onSchedulesGenerated() {
                sessionStorage.setItem('schedulesExist', 'true');
            }

            function onAllSchedulesDeleted() {
                sessionStorage.removeItem('schedulesExist');
                showNotification('All schedules deleted. You can now generate new schedules.', 'success');
                setTimeout(() => window.switchTab('generate'), 1000);
            }

            // ── Incomplete schedule warning ─────────────────────────────────────────────
            function toggleUnassignedCourses() {
                const list = document.getElementById('unassigned-courses-list');
                const icon = event.target.closest('button')?.querySelector('i');
                if (!list) return;
                list.classList.toggle('hidden');
                icon?.classList.toggle('fa-chevron-down');
                icon?.classList.toggle('fa-chevron-up');
            }

            function hideIncompleteWarning() {
                const b = document.getElementById('incomplete-schedule-banner');
                if (b) b.style.display = 'none';
            }

            // ── Init on page load ───────────────────────────────────────────────────────
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                const tabName = tab === 'schedule-list' ? 'schedule' : (tab || 'generate');
                _performTabSwitch(tabName); // use private fn so external JS overrides aren't called yet

                document.addEventListener('click', e => {
                    const dd = document.getElementById('printDropdown');
                    if (dd && !e.target.closest('#printDropdownBtn') && !dd.classList.contains('hidden')) {
                        dd.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') {
                        const sidebar = document.getElementById('sidebar');
                        if (sidebar?.style.display === 'none') {
                            ['manual', 'view'].forEach(t => {
                                const exitBtn = document.getElementById(`exit-fullscreen-${t}-btn`);
                                if (exitBtn && !exitBtn.classList.contains('hidden')) toggleFullScreen(t);
                            });
                        }
                    }
                });
            });
        </script>

        <!-- Include external JavaScript files -->
        <script src="/assets/js/generate_schedules.js?v=20250116_001"></script>
        <script src="/assets/js/manual_schedules.js?v=20250116_001"></script>
        <!-- Add these to your head section -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>