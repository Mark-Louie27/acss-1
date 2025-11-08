<?php
ob_start();
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
// Helper function to get consistent color for schedules
function getScheduleColorClass($schedule)
{
    $colors = [
        'bg-blue-100 border-blue-300 text-blue-800',
        'bg-green-100 border-green-300 text-green-800',
        'bg-purple-100 border-purple-300 text-purple-800',
        'bg-orange-100 border-orange-300 text-orange-800',
        'bg-pink-100 border-pink-300 text-pink-800',
        'bg-indigo-100 border-indigo-300 text-indigo-800',
        'bg-teal-100 border-teal-300 text-teal-800'
    ];

    if (isset($schedule['schedule_id'])) {
        $hash = crc32($schedule['schedule_id']);
        $index = abs($hash) % count($colors);
        return $colors[$index];
    }

    return $colors[array_rand($colors)];
}

?>

<link rel="stylesheet" href="/css/schedule_management.css">

<!-- Force Git to track this change -->


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

                            <button id="save-changes-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="saveAllChanges()">
                                <i class="fas fa-save"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <!-- View Toggle Button -->
                            <button id="toggle-view-btn" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="toggleViewMode()">
                                <i class="fas fa-list mr-1"></i>
                                <span>List View</span>
                            </button>

                            <button id="delete-all-btn-manual" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors text-sm" onclick="deleteAllSchedules()">
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
                <!-- Accurate Schedule Grid -->
                <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="min-w-full">
                        <!-- Header with days -->
                        <div class="grid grid-cols-7 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
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
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700">
                                <span class="hidden sm:inline">Saturday</span>
                                <span class="sm:hidden">Sat</span>
                            </div>
                        </div>

                        <!-- Accurate Time slots grid -->
                        <div id="schedule-grid" class="divide-y divide-gray-200">
                            <?php
                            // 🎯 ACCURATE TIME SLOT GENERATION BASED ON ACTUAL SCHEDULES
                            $allTimes = [];

                            // Add ALL unique start and end times from schedules
                            foreach ($schedules as $schedule) {
                                if ($schedule['start_time']) {
                                    $startTime = substr($schedule['start_time'], 0, 5);
                                    if (!in_array($startTime, $allTimes)) {
                                        $allTimes[] = $startTime;
                                    }
                                }
                                if ($schedule['end_time']) {
                                    $endTime = substr($schedule['end_time'], 0, 5);
                                    if (!in_array($endTime, $allTimes)) {
                                        $allTimes[] = $endTime;
                                    }
                                }
                            }

                            // If no schedules, use default time slots
                            if (empty($allTimes)) {
                                for ($hour = 7; $hour <= 20; $hour++) {
                                    $allTimes[] = sprintf('%02d:00', $hour);
                                    $allTimes[] = sprintf('%02d:30', $hour);
                                }
                            } else {
                                // Add strategic time points to ensure good coverage
                                $baseTimes = [
                                    '07:00',
                                    '07:30',
                                    '08:00',
                                    '08:30',
                                    '09:00',
                                    '09:30',
                                    '10:00',
                                    '10:30',
                                    '11:00',
                                    '11:30',
                                    '12:00',
                                    '12:30',
                                    '13:00',
                                    '13:30',
                                    '14:00',
                                    '14:30',
                                    '15:00',
                                    '15:30',
                                    '16:00',
                                    '16:30',
                                    '17:00',
                                    '17:30',
                                    '18:00',
                                    '18:30',
                                    '19:00',
                                    '19:30',
                                    '20:00'
                                ];

                                foreach ($baseTimes as $baseTime) {
                                    if (!in_array($baseTime, $allTimes)) {
                                        $allTimes[] = $baseTime;
                                    }
                                }
                            }

                            // Remove duplicates and sort properly
                            $allTimes = array_unique($allTimes);
                            usort($allTimes, function ($a, $b) {
                                return strtotime($a) - strtotime($b);
                            });

                            // Create clean time slots
                            $timeSlots = [];
                            for ($i = 0; $i < count($allTimes) - 1; $i++) {
                                $timeSlots[] = [
                                    'start' => $allTimes[$i],
                                    'end' => $allTimes[$i + 1]
                                ];
                            }

                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            ?>

                            <?php foreach ($timeSlots as $slot):
                                // Calculate row height based on duration
                                $start = strtotime($slot['start']);
                                $end = strtotime($slot['end']);
                                $duration = ($end - $start) / 60; // duration in minutes
                                $rowHeight = max(60, ($duration / 30) * 40); // Base height + proportional scaling
                            ?>
                                <div class="grid grid-cols-7 hover:bg-gray-50 transition-colors duration-200 schedule-row"
                                    style="min-height: <?php echo $rowHeight; ?>px;">

                                    <!-- Time Column -->
                                    <div class="px-3 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 sticky left-0 z-10 flex items-center"
                                        style="min-height: <?php echo $rowHeight; ?>px;">
                                        <div>
                                            <span class="text-sm hidden sm:block">
                                                <?php echo date('g:i A', strtotime($slot['start'])) . ' - ' . date('g:i A', strtotime($slot['end'])); ?>
                                            </span>
                                            <span class="text-xs sm:hidden">
                                                <?php echo $slot['start'] . '-' . $slot['end']; ?>
                                            </span>
                                            <br>
                                            <span class="text-xs text-gray-500 hidden sm:inline">
                                                (<?php echo $duration; ?> min)
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Day Columns -->
                                    <?php foreach ($days as $day): ?>
                                        <div class="px-1 py-1 border-r border-gray-200 last:border-r-0 relative drop-zone schedule-cell"
                                            data-day="<?php echo $day; ?>"
                                            data-start-time="<?php echo $slot['start']; ?>"
                                            data-end-time="<?php echo $slot['end']; ?>"
                                            style="min-height: <?php echo $rowHeight; ?>px;">

                                            <?php
                                            $schedulesInThisSlot = [];

                                            foreach ($schedules as $schedule) {
                                                if ($schedule['day_of_week'] !== $day) continue;

                                                $scheduleStart = $schedule['start_time'] ? substr($schedule['start_time'], 0, 5) : '';
                                                $scheduleEnd = $schedule['end_time'] ? substr($schedule['end_time'], 0, 5) : '';

                                                if (!$scheduleStart || !$scheduleEnd) continue;

                                                // 🎯 ACCURATE SCHEDULE MATCHING - Check if schedule starts EXACTLY in this slot
                                                $scheduleStartsHere = ($scheduleStart === $slot['start']);

                                                if ($scheduleStartsHere) {
                                                    $schedulesInThisSlot[] = $schedule;
                                                }
                                            }
                                            ?>

                                            <?php if (empty($schedulesInThisSlot)): ?>
                                                <!-- Empty slot - show add button -->
                                                <button onclick="openAddModalForSlot('<?php echo $day; ?>', '<?php echo $slot['start']; ?>', '<?php echo $slot['end']; ?>')"
                                                    class="w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print flex items-center justify-center"
                                                    style="min-height: <?php echo $rowHeight - 16; ?>px;">
                                                    <i class="fas fa-plus text-sm"></i>
                                                </button>
                                            <?php else: ?>
                                                <div class="space-y-1 h-full">
                                                    <?php foreach ($schedulesInThisSlot as $schedule):
                                                        // Calculate schedule duration for proper height
                                                        $scheduleStart = strtotime($schedule['start_time']);
                                                        $scheduleEnd = strtotime($schedule['end_time']);
                                                        $scheduleDuration = ($scheduleEnd - $scheduleStart) / 60;
                                                        $scheduleHeight = max(60, ($scheduleDuration / 30) * 40);

                                                        // Get consistent color for this schedule
                                                        $colorClass = getScheduleColorClass($schedule);
                                                    ?>
                                                        <div class="schedule-card <?php echo $colorClass; ?> p-2 rounded-lg border-l-4 draggable cursor-move text-xs full-access-card"
                                                            draggable="true"
                                                            data-schedule-id="<?php echo $schedule['schedule_id']; ?>"
                                                            data-year-level="<?php echo htmlspecialchars($schedule['year_level']); ?>"
                                                            data-section-name="<?php echo htmlspecialchars($schedule['section_name']); ?>"
                                                            data-room-name="<?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>"
                                                            data-start-time="<?php echo $schedule['start_time']; ?>"
                                                            data-end-time="<?php echo $schedule['end_time']; ?>"
                                                            data-full-access="true"
                                                            style="min-height: <?php echo $scheduleHeight - 16; ?>px;">

                                                            <div class="flex justify-between items-start mb-1">
                                                                <div class="font-semibold truncate flex-1">
                                                                    <?php echo htmlspecialchars($schedule['course_code']); ?>
                                                                </div>
                                                                <!-- Action buttons -->
                                                                <div class="flex space-x-1 flex-shrink-0 ml-1">
                                                                    <button onclick="event.stopPropagation(); editScheduleFromAnyCell('<?php echo $schedule['schedule_id']; ?>')"
                                                                        class="text-yellow-600 hover:text-yellow-700 no-print">
                                                                        <i class="fas fa-edit text-xs"></i>
                                                                    </button>
                                                                    <button onclick="event.stopPropagation(); openDeleteSingleModal(
                                                        '<?php echo $schedule['schedule_id']; ?>', 
                                                        '<?php echo htmlspecialchars($schedule['course_code']); ?>', 
                                                        '<?php echo htmlspecialchars($schedule['section_name']); ?>', 
                                                        '<?php echo htmlspecialchars($schedule['day_of_week']); ?>', 
                                                        '<?php echo date('g:i A', strtotime($schedule['start_time'])); ?>', 
                                                        '<?php echo date('g:i A', strtotime($schedule['end_time'])); ?>'
                                                    )" class="text-red-600 hover:text-red-700 no-print">
                                                                        <i class="fas fa-trash text-xs"></i>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="opacity-90 truncate">
                                                                <?php echo htmlspecialchars($schedule['section_name']); ?>
                                                            </div>

                                                            <div class="opacity-75 truncate">
                                                                <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                                            </div>

                                                            <div class="opacity-75 truncate">
                                                                <?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>
                                                            </div>

                                                            <div class="font-medium mt-1 text-xs">
                                                                <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?>
                                                            </div>

                                                            <div class="text-xs text-gray-500 mt-1">
                                                                Duration: <?php echo $scheduleDuration; ?> minutes
                                                            </div>
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
                                        <tr class="hover:bg-gray-50 transition-colors duration-200 schedule-row" data-schedule-id="<?php echo $schedule['schedule_id']; ?>">
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
                                <button id="delete-all-btn-view" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors text-sm" onclick="deleteAllSchedules()">
                                    <i class="fas fa-trash"></i>
                                    <span>Delete All</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Timetable - FIXED VERSION -->
                <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="min-w-full">
                        <!-- Header with days -->
                        <div class="grid grid-cols-7 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
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
                            <div class="px-2 py-3 text-sm font-semibold text-center text-gray-700">
                                <span class="hidden sm:inline">Saturday</span>
                                <span class="sm:hidden">Sat</span>
                            </div>
                        </div>

                        <!-- Time slots - FIXED -->
                        <div id="timetableGrid" class="divide-y divide-gray-200">
                            <?php
                            // Create proper time slots for the view tab
                            $viewTimeSlots = [
                                ['07:00', '08:00'],
                                ['08:00', '09:00'],
                                ['09:00', '10:00'],
                                ['10:00', '11:00'],
                                ['11:00', '12:00'],
                                ['12:00', '13:00'],
                                ['13:00', '14:00'],
                                ['14:00', '15:00'],
                                ['15:00', '16:00'],
                                ['16:00', '17:00'],
                                ['17:00', '18:00'],
                                ['18:00', '19:00'],
                                ['19:00', '20:00']
                            ];

                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            ?>

                            <?php if (!empty($viewTimeSlots)): ?>
                                <?php foreach ($viewTimeSlots as $time):
                                    // Validate time slots exist
                                    $startTime = !empty($time[0]) ? $time[0] : '07:00';
                                    $endTime = !empty($time[1]) ? $time[1] : '08:00';

                                    $duration = strtotime($endTime) - strtotime($startTime);
                                    $rowSpan = $duration / 1800; // 30-minute base
                                    $minHeight = max(60, $rowSpan * 40);
                                ?>
                                    <div class="grid grid-cols-8 hover:bg-gray-50 transition-colors duration-200"
                                        style="min-height: <?php echo $minHeight; ?>px;">

                                        <div class="px-3 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 flex items-center sticky left-0 z-10">
                                            <span class="text-sm hidden sm:block">
                                                <?php echo date('g:i A', strtotime($startTime)) . ' - ' . date('g:i A', strtotime($endTime)); ?>
                                            </span>
                                            <span class="text-xs sm:hidden">
                                                <?php echo $startTime . '-' . $endTime; ?>
                                            </span>
                                        </div>

                                        <?php foreach ($days as $day): ?>
                                            <div class="px-1 py-1 border-r border-gray-200 last:border-r-0 relative schedule-cell min-h-[80px]"
                                                data-day="<?php echo $day; ?>"
                                                data-start-time="<?php echo $startTime; ?>"
                                                data-end-time="<?php echo $endTime; ?>">

                                                <?php
                                                $schedulesForSlot = [];

                                                if (!empty($schedules)) {
                                                    foreach ($schedules as $schedule) {
                                                        if (empty($schedule['day_of_week']) || $schedule['day_of_week'] !== $day) continue;

                                                        $scheduleStart = !empty($schedule['start_time']) ? substr($schedule['start_time'], 0, 5) : '';
                                                        $scheduleEnd = !empty($schedule['end_time']) ? substr($schedule['end_time'], 0, 5) : '';

                                                        if (empty($scheduleStart) || empty($scheduleEnd)) continue;

                                                        // Check if schedule overlaps with this time slot
                                                        $scheduleOverlaps = (
                                                            $scheduleStart < $endTime &&
                                                            $scheduleEnd > $startTime
                                                        );

                                                        if ($scheduleOverlaps) {
                                                            $schedulesForSlot[] = $schedule;
                                                        }
                                                    }
                                                }
                                                ?>

                                                <?php if (!empty($schedulesForSlot)): ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($schedulesForSlot as $schedule):
                                                            $colorClass = getScheduleColorClass($schedule);
                                                        ?>
                                                            <div class="schedule-card <?php echo $colorClass; ?> p-2 rounded-lg border-l-4 schedule-item"
                                                                data-year-level="<?php echo !empty($schedule['year_level']) ? htmlspecialchars($schedule['year_level']) : ''; ?>"
                                                                data-section-name="<?php echo !empty($schedule['section_name']) ? htmlspecialchars($schedule['section_name']) : ''; ?>"
                                                                data-room-name="<?php echo !empty($schedule['room_name']) ? htmlspecialchars($schedule['room_name']) : 'Online'; ?>">

                                                                <div class="font-semibold text-xs truncate mb-1">
                                                                    <?php echo !empty($schedule['course_code']) ? htmlspecialchars($schedule['course_code']) : ''; ?>
                                                                </div>
                                                                <div class="text-xs opacity-90 truncate mb-1">
                                                                    <?php echo !empty($schedule['section_name']) ? htmlspecialchars($schedule['section_name']) : ''; ?>
                                                                </div>
                                                                <div class="text-xs opacity-75 truncate">
                                                                    <?php echo !empty($schedule['faculty_name']) ? htmlspecialchars($schedule['faculty_name']) : ''; ?>
                                                                </div>
                                                                <div class="text-xs opacity-75 truncate">
                                                                    <?php echo !empty($schedule['room_name']) ? htmlspecialchars($schedule['room_name']) : 'Online'; ?>
                                                                </div>
                                                                <div class="text-xs font-medium mt-1">
                                                                    <?php
                                                                    $displayStart = !empty($schedule['start_time']) ? date('g:i A', strtotime($schedule['start_time'])) : '';
                                                                    $displayEnd = !empty($schedule['end_time']) ? date('g:i A', strtotime($schedule['end_time'])) : '';
                                                                    echo $displayStart . ' - ' . $displayEnd;
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback if no time slots -->
                                <div class="p-8 text-center text-gray-500 col-span-7">
                                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                                    <p>No schedule data available for viewing</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay - FIXED -->
        <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-md flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl p-8 text-center min-w-[400px]">
                <!-- Loading Spinner -->
                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-yellow-200 rounded-full"></div>
                        <div class="w-16 h-16 border-4 border-yellow-500 rounded-full animate-spin border-t-transparent absolute top-0 left-0"></div>
                    </div>
                </div>

                <!-- Loading Text -->
                <h3 class="text-xl font-bold text-gray-800 mb-2">Generating Schedules</h3>
                <p class="text-gray-600 mb-6">Please wait while we create your schedules...</p>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                    <div id="progress-bar" class="bg-yellow-500 h-3 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                </div>

                <!-- Progress Text -->
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Initializing...</span>
                    <span id="progress-text">0%</span>
                </div>

                <!-- Cancel Button -->
                <button id="cancel-generation" class="mt-6 px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                    Cancel Generation
                </button>
            </div>
        </div>

        <!-- Generation Report Modal -->
        <div id="report-modal" class="fixed inset-0 bg-opacity-30 backdrop-blur-md flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" id="report-title">Schedule Generation Report</h3>
                    <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="report-content" class="mb-6 text-gray-700">
                    <!-- Report content will be dynamically updated -->
                </div>
                <div class="flex justify-end">
                    <button onclick="closeReportModal()" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg">Close</button>
                </div>
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
                            <input type="time" id="start-time" name="start_time_display"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change"
                                onchange="updateTimeFields(); calculateAutoEndTime()"
                                step="300" min="07:00" max="21:00" required>
                            <p class="text-xs text-gray-500 mt-1">Format: HH:MM (24-hour)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Time</label>
                            <input type="time" id="end-time" name="end_time_display"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 validate-on-change"
                                onchange="updateTimeFields()"
                                step="300" min="07:00" max="21:00" required>
                            <div class="flex space-x-2 mt-2">
                                <button type="button" onclick="setDuration(60)" class="flex-1 px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs rounded transition-colors">
                                    1hr
                                </button>
                                <button type="button" onclick="setDuration(90)" class="flex-1 px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-xs rounded transition-colors">
                                    1.5hr
                                </button>
                                <button type="button" onclick="setDuration(180)" class="flex-1 px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs rounded transition-colors">
                                    3hr
                                </button>
                            </div>
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

        <style>
            .conflict-tooltip {
                position: absolute;
                bottom: -25px;
                left: 0;
                background: #fee2e2;
                color: #dc2626;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 10;
                border: 1px solid #fca5a5;
            }

            .conflict-tooltip::after {
                content: '';
                position: absolute;
                top: -5px;
                left: 10px;
                border: 5px solid transparent;
                border-bottom-color: #fee2e2;
            }
        </style>


        <script>
            // Global data
            window.scheduleData = <?php echo json_encode($schedules); ?> || [];
            window.jsData = <?php echo json_encode($jsData); ?>;
            window.departmentId = window.jsData.departmentId;
            window.currentSemester = window.jsData.currentSemester;
            window.rawSectionsData = window.jsData.sectionsData || [];
            window.currentAcademicYear = window.jsData.currentAcademicYear || "";
            window.faculty = window.jsData.faculty || [];
            window.classrooms = window.jsData.classrooms || [];
            window.curricula = window.jsData.curricula || [];
            // Get curriculum courses from jsData
            window.curriculumCourses = window.jsData.curriculumCourses || [];

            // Transform sections data
            window.sectionsData = Array.isArray(window.rawSectionsData) ? window.rawSectionsData.map((s, index) => ({
                section_id: s.section_id ?? (index + 1),
                section_name: s.section_name ?? '',
                year_level: s.year_level ?? 'Unknown',
                academic_year: s.academic_year ?? '',
                current_students: s.current_students ?? 0,
                max_students: s.max_students ?? 30,
                semester: s.semester ?? '',
                is_active: s.is_active ?? 1
            })) : [];

            function toggleViewMode() {
                const gridView = document.getElementById('grid-view');
                const listView = document.getElementById('list-view');
                const toggleBtn = document.getElementById('toggle-view-btn');

                if (gridView.classList.contains('hidden')) {
                    // Switch to Grid View
                    gridView.classList.remove('hidden');
                    listView.classList.add('hidden');
                    toggleBtn.innerHTML = '<i class="fas fa-list mr-1"></i><span>List View</span>';
                } else {
                    // Switch to List View
                    gridView.classList.add('hidden');
                    listView.classList.remove('hidden');
                    toggleBtn.innerHTML = '<i class="fas fa-th mr-1"></i><span>Grid View</span>';
                }
            }

            // Clear filters for manual tab
            function clearFiltersManual() {
                document.getElementById('filter-year-manual').value = '';
                document.getElementById('filter-section-manual').value = '';
                document.getElementById('filter-room-manual').value = '';
                filterSchedulesManual();
            }

            // Refresh manual view
            function refreshManualView() {
                // You can add refresh logic here
                location.reload(); // Simple refresh for now
            }

            // Filter schedules for manual tab
            function filterSchedulesManual() {
                const yearLevel = document.getElementById('filter-year-manual').value;
                const section = document.getElementById('filter-section-manual').value;
                const room = document.getElementById('filter-room-manual').value;

                const scheduleCards = document.querySelectorAll('#schedule-grid .schedule-card');

                scheduleCards.forEach(card => {
                    const cardYearLevel = card.getAttribute('data-year-level');
                    const cardSectionName = card.getAttribute('data-section-name');
                    const cardRoomName = card.getAttribute('data-room-name');

                    const matchesYear = !yearLevel || cardYearLevel === yearLevel;
                    const matchesSection = !section || cardSectionName === section;
                    const matchesRoom = !room || cardRoomName === room;

                    if (matchesYear && matchesSection && matchesRoom) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Enhanced full screen function
            function toggleFullScreen(tab) {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.querySelector('.main-content');
                const header = document.querySelector('header');
                const fullscreenBtn = document.getElementById(`fullscreen-${tab}-btn`);
                const exitFullscreenBtn = document.getElementById(`exit-fullscreen-${tab}-btn`);

                if (sidebar.style.display !== 'none') {
                    // Enter full screen mode
                    sidebar.style.display = 'none';
                    header.style.display = 'none';
                    mainContent.style.marginLeft = '0';
                    mainContent.style.padding = '0';
                    mainContent.classList.add('fullscreen-mode');

                    // Update buttons
                    fullscreenBtn.classList.add('hidden');
                    exitFullscreenBtn.classList.remove('hidden');

                    // Add fullscreen styles with higher z-index for modals
                    document.body.style.overflow = 'hidden';
                    mainContent.style.width = '100vw';
                    mainContent.style.height = '100vh';
                    mainContent.style.position = 'fixed';
                    mainContent.style.top = '0';
                    mainContent.style.left = '0';
                    mainContent.style.zIndex = '1000';
                    mainContent.style.backgroundColor = 'white';

                    // Ensure modals have higher z-index in full screen
                    const modals = document.querySelectorAll('.modal-overlay');
                    modals.forEach(modal => {
                        modal.style.zIndex = '1001';
                    });

                } else {
                    // Exit full screen mode
                    sidebar.style.display = '';
                    header.style.display = '';
                    mainContent.style.marginLeft = '';
                    mainContent.style.padding = '';
                    mainContent.classList.remove('fullscreen-mode');

                    // Update buttons
                    fullscreenBtn.classList.remove('hidden');
                    exitFullscreenBtn.classList.add('hidden');

                    // Remove fullscreen styles
                    document.body.style.overflow = '';
                    mainContent.style.width = '';
                    mainContent.style.height = '';
                    mainContent.style.position = '';
                    mainContent.style.top = '';
                    mainContent.style.left = '';
                    mainContent.style.zIndex = '';
                    mainContent.style.backgroundColor = '';

                    // Reset modal z-index
                    const modals = document.querySelectorAll('.modal-overlay');
                    modals.forEach(modal => {
                        modal.style.zIndex = '';
                    });
                }
            }

            // Enhanced modal functions for full screen compatibility
            function showModal() {
                const modal = document.getElementById("schedule-modal");
                if (modal) {
                    modal.classList.remove("hidden");
                    modal.classList.add("flex");

                    // Ensure modal is on top in full screen mode
                    const mainContent = document.querySelector('.main-content');
                    if (mainContent.classList.contains('fullscreen-mode')) {
                        modal.style.zIndex = '1001';
                    }

                    console.log("Modal shown");
                } else {
                    console.error("Modal element not found!");
                }
            }

            function closeModal() {
                const modal = document.getElementById("schedule-modal");
                if (modal) {
                    modal.classList.add("hidden");
                    modal.classList.remove("flex");
                    modal.style.zIndex = ''; // Reset z-index
                }

                const form = document.getElementById("schedule-form");
                if (form) form.reset();

                // Reset conflict styles when closing modal
                resetConflictStyles();

                currentEditingId = null;
            }

            // Handle ESC key to exit full screen
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar.style.display === 'none') {
                        // Exit full screen for both tabs
                        const manualFullscreenBtn = document.getElementById('fullscreen-manual-btn');
                        const viewFullscreenBtn = document.getElementById('fullscreen-view-btn');

                        if (!manualFullscreenBtn.classList.contains('hidden')) {
                            toggleFullScreen('manual');
                        } else if (!viewFullscreenBtn.classList.contains('hidden')) {
                            toggleFullScreen('view');
                        }
                    }
                }
            });

            // Handle browser fullscreen API (optional enhancement)
            function toggleNativeFullScreen() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(err => {
                        console.log(`Error attempting to enable full-screen mode: ${err.message}`);
                    });
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    }
                }
            }

            // Store the original switchTab function BEFORE redefining it
            const originalSwitchTab = window.switchTab;

            // Enhanced switchTab function with full screen handling
            window.switchTab = function(tabName) {
                // If we're in full screen mode, exit it first
                const sidebar = document.getElementById('sidebar');
                if (sidebar.style.display === 'none') {
                    // Exit full screen for current tab
                    const currentTab = document.querySelector('.tab-content:not(.hidden)').id.replace('content-', '');
                    toggleFullScreen(currentTab);

                    // Small delay to ensure DOM updates before switching tabs
                    setTimeout(() => {
                        performTabSwitch(tabName);
                    }, 50);
                } else {
                    performTabSwitch(tabName);
                }
            };

            // Separate function to handle the actual tab switching
            function performTabSwitch(tabName) {
                // Remove active classes from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('bg-yellow-500', 'text-white');
                    btn.classList.add('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
                });

                // Add active class to selected tab
                const targetTab = document.getElementById(`tab-${tabName}`);
                if (targetTab) {
                    targetTab.classList.add('bg-yellow-500', 'text-white');
                    targetTab.classList.remove('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
                }

                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Show selected tab content
                const targetContent = document.getElementById(`content-${tabName}`);
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                }

                // Update URL
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName === 'schedule' ? 'schedule-list' : tabName);
                window.history.pushState({}, '', url);

                // Automatically refresh relevant UI when switching tabs
                try {
                    if (tabName === 'manual') {
                        // Refresh manual grid and list views without a full page reload
                        if (typeof updateManualGridView === 'function') {
                            updateManualGridView(window.scheduleData || []);
                        }
                        if (typeof updateListView === 'function') {
                            updateListView(window.scheduleData || []);
                        }
                        // Re-apply manual filters if any
                        if (typeof filterSchedulesManual === 'function') {
                            filterSchedulesManual();
                        }
                    } else if (tabName === 'schedule') {
                        // Refresh the weekly view and list view
                        if (typeof updateViewScheduleTab === 'function') {
                            updateViewScheduleTab(window.scheduleData || []);
                        }
                        if (typeof updateListView === 'function') {
                            updateListView(window.scheduleData || []);
                        }
                        // Re-apply view filters if any
                        if (typeof filterSchedules === 'function') {
                            filterSchedules();
                        }
                    } else if (tabName === 'generate') {
                        // Optionally refresh generate-related data (curricula/sections)
                        // If you want a full reload for the generate tab, uncomment next line
                        // location.reload();
                        // Otherwise you can refresh only specific UI pieces if functions exist
                        if (typeof updateCourses === 'function') {
                            // Attempt to update courses list based on current curriculum selection
                            updateCourses();
                        }
                    }
                } catch (e) {
                    console.error('Error refreshing after tab switch:', e);
                }
            }

            // Make sure the original switchTab function exists
            if (typeof window.switchTab === 'undefined') {
                window.switchTab = function(tabName) {
                    performTabSwitch(tabName);
                };
            }

            function formatTime(time) {
                const [hours, minutes] = time.split(':');
                const date = new Date(2000, 0, 1, hours, minutes);
                return date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            function escapeHtml(unsafe) {
                if (unsafe === null || unsafe === undefined) {
                    return '';
                }

                // Convert to string first
                const safeString = String(unsafe);

                // If it's empty after conversion, return empty string
                if (!safeString) {
                    return '';
                }

                return safeString
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                if (!notification) {
                    const notificationDiv = document.createElement('div');
                    notificationDiv.id = 'notification';
                    notificationDiv.className = 'mb-6';
                    notificationDiv.innerHTML = `
                        <div class="flex items-center p-4 rounded-lg ${type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}">
                            <div class="flex-shrink-0">
                                <i class="fas ${type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'} text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium ${type === 'success' ? 'text-green-800' : 'text-red-800'}" id="notificationText">${message}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button class="${type === 'success' ? 'text-green-400 hover:text-green-600' : 'text-red-400 hover:text-red-600'}" onclick="hideNotification()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    document.querySelector('.max-w-7xl').insertBefore(notificationDiv, document.querySelector('.max-w-7xl').firstElementChild.nextElementSibling);
                } else {
                    document.getElementById('notificationText').textContent = message;
                    notification.classList.remove('hidden');
                }

                setTimeout(() => hideNotification(), 5000);
            }

            function hideNotification() {
                const notification = document.getElementById('notification');
                if (notification) notification.classList.add('hidden');
            }

            function togglePrintDropdown() {
                const dropdown = document.getElementById('printDropdown');
                dropdown.classList.toggle('hidden');
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

            // Dynamic view schedule tab that shows ALL schedules
            function updateViewScheduleTab(schedules) {
                const viewGrid = document.getElementById('timetableGrid');
                if (!viewGrid) return;

                viewGrid.innerHTML = '';

                // Generate time slots for view tab (can be different from manual tab)
                const timeSlots = generateViewTimeSlots(schedules);
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                timeSlots.forEach(timeSlot => {
                    const duration = (new Date(`2000-01-01 ${timeSlot.end}`) - new Date(`2000-01-01 ${timeSlot.start}`)) / 1000;
                    const rowSpan = Math.max(1, duration / 1800); // 30-minute base
                    const minHeight = Math.max(60, rowSpan * 40); // Minimum height

                    const row = document.createElement('div');
                    row.className = `grid grid-cols-8 hover:bg-gray-50 transition-colors duration-200`;
                    row.style.minHeight = `${minHeight}px`;

                    // Time cell
                    const timeCell = document.createElement('div');
                    timeCell.className = 'px-3 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 flex items-center sticky left-0 z-10';
                    timeCell.innerHTML = `
                        <span class="text-sm hidden sm:block">${formatTime(timeSlot.start)} - ${formatTime(timeSlot.end)}</span>
                        <span class="text-xs sm:hidden">${timeSlot.start}-${timeSlot.end}</span>
                    `;
                    row.appendChild(timeCell);

                    // Day cells
                    days.forEach(day => {
                        const cell = document.createElement('div');
                        cell.className = `px-1 py-1 border-r border-gray-200 last:border-r-0 relative schedule-cell`;
                        cell.style.minHeight = `${minHeight}px`;
                        cell.dataset.day = day;
                        cell.dataset.startTime = timeSlot.start;
                        cell.dataset.endTime = timeSlot.end;

                        // Find ALL schedules that occur during this time slot
                        const schedulesForSlot = schedules.filter(schedule => {
                            if (schedule.day_of_week !== day) return false;

                            const scheduleStart = schedule.start_time ? schedule.start_time.substring(0, 5) : '';
                            const scheduleEnd = schedule.end_time ? schedule.end_time.substring(0, 5) : '';

                            if (!scheduleStart || !scheduleEnd) return false;

                            // Check if schedule overlaps with this time slot
                            const slotStart = timeSlot.start;
                            const slotEnd = timeSlot.end;

                            const scheduleOverlaps = (
                                scheduleStart < slotEnd &&
                                scheduleEnd > slotStart
                            );

                            return scheduleOverlaps;
                        });

                        if (schedulesForSlot.length > 0) {
                            const schedulesContainer = document.createElement('div');
                            schedulesContainer.className = 'space-y-1 h-full';

                            schedulesForSlot.forEach(schedule => {
                                const scheduleStart = schedule.start_time ? schedule.start_time.substring(0, 5) : '';
                                const isExactStart = (scheduleStart === timeSlot.start);

                                const scheduleItem = createViewScheduleItem(schedule, isExactStart);
                                schedulesContainer.appendChild(scheduleItem);
                            });

                            cell.appendChild(schedulesContainer);
                        }

                        row.appendChild(cell);
                    });

                    viewGrid.appendChild(row);
                });
            }

            function filterSchedules() {
                const yearLevel = document.getElementById('filter-year').value;
                const section = document.getElementById('filter-section').value;
                const room = document.getElementById('filter-room').value;
                const scheduleCells = document.querySelectorAll('#timetableGrid .schedule-cell');

                scheduleCells.forEach(cell => {
                    const items = cell.querySelectorAll('.schedule-item');
                    let shouldShow = false;

                    items.forEach(item => {
                        const itemYearLevel = item.getAttribute('data-year-level');
                        const itemSectionName = item.getAttribute('data-section-name');
                        const itemRoomName = item.getAttribute('data-room-name');
                        const matchesYear = !yearLevel || itemYearLevel === yearLevel;
                        const matchesSection = !section || itemSectionName === section;
                        const matchesRoom = !room || itemRoomName === room;

                        if (matchesYear && matchesSection && matchesRoom) {
                            item.style.display = 'block';
                            shouldShow = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    cell.style.display = shouldShow ? 'block' : 'block';
                });
            }


            // Enhanced list view that shows ALL schedules
            function updateListView(schedules) {
                const listView = document.getElementById('list-view');
                if (!listView) return;

                const tbody = listView.querySelector('tbody');
                if (!tbody) return;

                tbody.innerHTML = '';

                if (schedules.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2"></i>
                <p class="mt-2">No schedules found</p>
            </td>
        `;
                    tbody.appendChild(emptyRow);
                    return;
                }

                // Sort schedules by day and time for better organization
                const dayOrder = {
                    Monday: 1,
                    Tuesday: 2,
                    Wednesday: 3,
                    Thursday: 4,
                    Friday: 5,
                    Saturday: 6,
                    Sunday: 7
                };
                const sortedSchedules = [...schedules].sort((a, b) => {
                    const dayCompare = dayOrder[a.day_of_week] - dayOrder[b.day_of_week];
                    if (dayCompare !== 0) return dayCompare;

                    // Then sort by start time
                    const aTime = a.start_time || '00:00';
                    const bTime = b.start_time || '00:00';
                    return aTime.localeCompare(bTime);
                });

                sortedSchedules.forEach(schedule => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 transition-colors duration-200 schedule-row';
                    row.dataset.scheduleId = schedule.schedule_id;

                    row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                ${schedule.course_code || ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${schedule.section_name || ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${schedule.year_level || ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${schedule.faculty_name || ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${schedule.day_of_week || ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${schedule.start_time && schedule.end_time ? 
                    `${schedule.start_time.substring(0, 5)} - ${schedule.end_time.substring(0, 5)}` : 
                    ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${schedule.room_name || 'Online'}
            </td>
            <td class="px-4 py-3 text-center">
                <div class="flex space-x-2 justify-center">
                    <button onclick="editSchedule('${schedule.schedule_id || ''}')" class="text-yellow-600 hover:text-yellow-700 text-sm no-print">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="openDeleteSingleModal(
                        '${schedule.schedule_id || ''}', 
                        '${schedule.course_code || ''}', 
                        '${schedule.section_name || ''}', 
                        '${schedule.day_of_week || ''}', 
                        '${schedule.start_time ? formatTime(schedule.start_time.substring(0, 5)) : ''}', 
                        '${schedule.end_time ? formatTime(schedule.end_time.substring(0, 5)) : ''}'
                    )" class="text-red-600 hover:text-red-700 text-sm no-print">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

                    tbody.appendChild(row);
                });
            }


            function clearFilters() {
                document.getElementById('filter-year').value = '';
                document.getElementById('filter-section').value = '';
                document.getElementById('filter-room').value = '';
                filterSchedules();
            }

            // Safe function to update schedule display without escapeHtml issues
            // 🎯 Replace the safeUpdateScheduleDisplay function
            function safeUpdateScheduleDisplay(schedules) {
                window.scheduleData = schedules;
                console.log("📊 Updating display with accurate time slots for", schedules.length, "schedules");

                updateScheduleGridWithAccurateSlots(schedules);
            }
            // Updated function to create schedule card with continuation support
            function createSafeScheduleCard(schedule, isStartCell = true) {
                const card = document.createElement('div');

                const colors = [
                    'bg-blue-100 border-blue-300 text-blue-800',
                    'bg-green-100 border-green-300 text-green-800',
                    'bg-purple-100 border-purple-300 text-purple-800',
                    'bg-orange-100 border-orange-300 text-orange-800',
                    'bg-pink-100 border-pink-300 text-pink-800'
                ];

                const colorIndex = schedule.schedule_id ?
                    (parseInt(schedule.schedule_id) % colors.length) :
                    Math.floor(Math.random() * colors.length);
                const colorClass = colors[colorIndex];

                card.className = `schedule-card ${colorClass} p-2 rounded-lg border-l-4 draggable cursor-move text-xs`;
                card.draggable = true;
                card.dataset.scheduleId = schedule.schedule_id || '';
                card.dataset.yearLevel = schedule.year_level || '';
                card.dataset.sectionName = schedule.section_name || '';
                card.dataset.roomName = schedule.room_name || 'Online';

                // Apply opacity for continuation schedules
                if (!isStartCell) {
                    card.style.opacity = '0.6';
                }

                card.innerHTML = `
        <div class="flex justify-between items-start mb-1">
            <div class="font-semibold truncate flex-1">
                ${schedule.course_code || ''}
                ${!isStartCell ? '<span class="text-xs text-gray-500">(cont.)</span>' : ''}
            </div>
            ${isStartCell ? `
            <div class="flex space-x-1 flex-shrink-0 ml-1">
                <button onclick="editSchedule('${schedule.schedule_id || ''}')" class="text-yellow-600 hover:text-yellow-700 no-print">
                    <i class="fas fa-edit text-xs"></i>
                </button>
                <button onclick="openDeleteSingleModal(
                    '${schedule.schedule_id || ''}', 
                    '${schedule.course_code || ''}', 
                    '${schedule.section_name || ''}', 
                    '${schedule.day_of_week || ''}', 
                    '${schedule.start_time ? formatTime(schedule.start_time.substring(0, 5)) : ''}', 
                    '${schedule.end_time ? formatTime(schedule.end_time.substring(0, 5)) : ''}'
                )" class="text-red-600 hover:text-red-700 no-print">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
            ` : ''}
        </div>
        <div class="opacity-90 truncate">
            ${schedule.section_name || ''}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.faculty_name || ''}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.room_name || 'Online'}
        </div>
        <div class="font-medium mt-1 hidden sm:block">
            ${schedule.start_time && schedule.end_time ? 
                `${formatTime(schedule.start_time.substring(0, 5))} - ${formatTime(schedule.end_time.substring(0, 5))}` : 
                ''}
        </div>
    `;

                return card;
            }

            // Safe function to create schedule item for view tab
            function createSafeScheduleItem(schedule) {
                const item = document.createElement('div');

                // Use the same color system as PHP for consistency
                const colors = [
                    'bg-blue-100 border-blue-300 text-blue-800',
                    'bg-green-100 border-green-300 text-green-800',
                    'bg-purple-100 border-purple-300 text-purple-800',
                    'bg-orange-100 border-orange-300 text-orange-800',
                    'bg-pink-100 border-pink-300 text-pink-800'
                ];

                // Generate consistent color based on schedule_id
                const colorIndex = schedule.schedule_id ?
                    (parseInt(schedule.schedule_id) % colors.length) :
                    Math.floor(Math.random() * colors.length);
                const colorClass = colors[colorIndex];

                item.className = `schedule-card ${colorClass} p-2 rounded-lg border-l-4 mb-1 schedule-item`;
                item.dataset.yearLevel = schedule.year_level || '';
                item.dataset.sectionName = schedule.section_name || '';
                item.dataset.roomName = schedule.room_name || 'Online';

                item.innerHTML = `
                    <div class="font-semibold text-xs truncate mb-1">
                        ${schedule.course_code || ''}
                    </div>
                    <div class="text-xs opacity-90 truncate mb-1">
                        ${schedule.section_name || ''}
                    </div>
                    <div class="text-xs opacity-75 truncate">
                        ${schedule.faculty_name || ''}
                    </div>
                    <div class="text-xs opacity-75 truncate">
                        ${schedule.room_name || 'Online'}
                    </div>
                    <div class="text-xs font-medium mt-1">
                        ${schedule.start_time && schedule.end_time ? 
                            `${formatTime(schedule.start_time.substring(0, 5))} - ${formatTime(schedule.end_time.substring(0, 5))}` : 
                            ''}
                    </div>
                `;

                return item;
            }

            document.getElementById('generate-btn').addEventListener('click', function() {
                const form = document.getElementById('generate-form');
                const curriculumId = form.querySelector('#curriculum_id').value;

                if (!curriculumId) {
                    showNotification('Please select a curriculum.', 'error');
                    return;
                }

                // Show loading overlay
                const loadingOverlay = document.getElementById('loading-overlay');
                loadingOverlay.classList.remove('hidden');

                // Build form data
                const formData = new URLSearchParams({
                    action: 'generate_schedule',
                    curriculum_id: curriculumId,
                    semester_id: form.querySelector('[name="semester_id"]').value
                });

                fetch('/chair/generate-schedules', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    })
                    .then(response => {
                        // Check if response is ok
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data); // Debug log

                        // Hide loading overlay
                        loadingOverlay.classList.add('hidden');

                        if (!data.success) {
                            showNotification(data.message || 'Error generating schedules', 'error');
                            return; // Stop here if generation failed
                        }

                        // Update schedule data ONLY if successful
                        window.scheduleData = data.schedules || [];

                        // Update display first
                        safeUpdateScheduleDisplay(window.scheduleData);

                        // THEN show modal after display is updated
                        showReportModal(data);

                    })
                    .catch(error => {
                        loadingOverlay.classList.add('hidden');
                        console.error('Generate error:', error);
                        showNotification('Error generating schedules: ' + error.message, 'error');
                    });
            });

            // Helper function to get consistent color for a schedule
            function getScheduleColor(schedule) {
                const colors = [
                    'bg-blue-100 border-blue-300 text-blue-800',
                    'bg-green-100 border-green-300 text-green-800',
                    'bg-purple-100 border-purple-300 text-purple-800',
                    'bg-orange-100 border-orange-300 text-orange-800',
                    'bg-pink-100 border-pink-300 text-pink-800'
                ];

                if (schedule.schedule_id) {
                    // Use schedule_id for consistent coloring
                    return colors[parseInt(schedule.schedule_id) % colors.length];
                } else if (schedule.course_code) {
                    // Use course_code hash for new schedules
                    let hash = 0;
                    for (let i = 0; i < schedule.course_code.length; i++) {
                        hash = ((hash << 5) - hash) + schedule.course_code.charCodeAt(i);
                        hash = hash & hash;
                    }
                    return colors[Math.abs(hash) % colors.length];
                } else {
                    // Fallback to random
                    return colors[Math.floor(Math.random() * colors.length)];
                }
            }

            // NEW: Separate function to show report modal
            function showReportModal(data) {
                const reportModal = document.getElementById('report-modal');
                const reportContent = document.getElementById('report-content');
                const reportTitle = document.getElementById('report-title');

                let statusText, statusClass;

                // Determine status based on results
                if (!data.schedules || data.schedules.length === 0) {
                    statusText = 'No schedules were created. Please check if there are available sections, courses, faculty, and rooms.';
                    statusClass = 'text-red-600 bg-red-50 border-red-200';
                    reportTitle.textContent = 'Schedule Generation Failed';
                } else if (data.unassignedCourses && data.unassignedCourses.length > 0) {
                    statusText = `Partial success. ${data.unassignedCourses.length} courses could not be scheduled: ${data.unassignedCourses.map(c => c.course_code).join(', ')}`;
                    statusClass = 'text-yellow-600 bg-yellow-50 border-yellow-200';
                    reportTitle.textContent = 'Schedule Generation Partially Complete';
                } else {
                    statusText = 'All schedules generated successfully!';
                    statusClass = 'text-green-600 bg-green-50 border-green-200';
                    reportTitle.textContent = 'Schedule Generation Complete';
                }

                // Build report content
                reportContent.innerHTML = `
                    <div class="p-4 ${statusClass} border rounded-lg mb-4">
                        <p class="font-semibold">${statusText}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white p-3 rounded-lg text-center border border-gray-200">
                            <div class="text-2xl font-bold ${statusClass.split(' ')[0]}">${data.totalCourses || 0}</div>
                            <div class="text-gray-600 mt-1">Total Courses</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center border border-gray-200">
                            <div class="text-2xl font-bold ${statusClass.split(' ')[0]}">${data.totalSections || 0}</div>
                            <div class="text-gray-600 mt-1">Sections</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center border border-gray-200">
                            <div class="text-2xl font-bold ${statusClass.split(' ')[0]}">${data.successRate || '0%'}</div>
                            <div class="text-gray-600 mt-1">Success Rate</div>
                        </div>
                    </div>
                    ${data.unassignedCourses && data.unassignedCourses.length > 0 ? `
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm font-medium text-yellow-800 mb-2">Unscheduled Courses:</p>
                            <ul class="text-sm text-yellow-700 list-disc list-inside">
                                ${data.unassignedCourses.map(c => `<li>${c.course_code}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                `;

                reportTitle.className = `text-lg font-semibold ${statusClass.split(' ')[0]}`;

                // Show the modal
                reportModal.classList.remove('hidden');
                reportModal.classList.add('flex');

                // Update generation results card if it exists
                const generationResults = document.getElementById('generation-results');
                if (generationResults && data.schedules && data.schedules.length > 0) {
                    generationResults.classList.remove('hidden');
                    document.getElementById('total-courses').textContent = data.totalCourses || 0;
                    document.getElementById('total-sections').textContent = data.totalSections || 0;
                    document.getElementById('success-rate').textContent = data.successRate || '0%';
                }
            }

            function closeReportModal() {
                const reportModal = document.getElementById('report-modal');
                reportModal.classList.add('hidden');
            }

            // Debug function to check schedule data
            function debugSchedules() {
                console.log("=== SCHEDULE DEBUG INFO ===");
                console.log("Total schedules:", window.scheduleData.length);

                // Check for 7:30-10:30 schedules specifically
                const targetSchedules = window.scheduleData.filter(s => {
                    const start = s.start_time ? s.start_time.substring(0, 5) : '';
                    const end = s.end_time ? s.end_time.substring(0, 5) : '';
                    return start === '07:30' && end === '10:30';
                });

                console.log("7:30-10:30 schedules:", targetSchedules);

                // Check all time formats
                window.scheduleData.forEach(s => {
                    console.log(`Schedule: ${s.course_code} | ${s.day_of_week} | ${s.start_time} - ${s.end_time}`);
                });

                console.log("=== END DEBUG ===");
            }

            // Call this in your DOMContentLoaded to check initial state
            document.addEventListener('DOMContentLoaded', function() {
                // ... existing code ...

                // Add debug
                setTimeout(() => {
                    debugSchedules();
                    safeUpdateScheduleDisplay(window.scheduleData);
                }, 1000);
            });

            // Initialize page
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                const reportModal = document.getElementById('report-modal');

                if (tab === 'schedule-list') switchTab('schedule');
                else if (tab === 'manual') switchTab('manual');
                else switchTab('generate');

                if (reportModal) {
                    reportModal.addEventListener('click', function(e) {
                        if (e.target === reportModal) {
                            closeReportModal();
                        }
                    });
                }
            });

            // Dynamic manual grid view that handles any time
            function updateManualGridView(schedules) {
                const manualGrid = document.getElementById("schedule-grid");
                if (!manualGrid) return;

                manualGrid.innerHTML = "";

                // Generate dynamic time slots based on ALL schedule times
                const timeSlots = generateDynamicTimeSlots(schedules);
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                console.log("Generated time slots:", timeSlots);

                timeSlots.forEach(timeSlot => {
                    const row = document.createElement('div');
                    row.className = `grid grid-cols-8 min-h-[80px] hover:bg-gray-50 transition-colors duration-200`;

                    // Time column
                    const timeCell = document.createElement('div');
                    timeCell.className = 'px-3 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 sticky left-0 z-10 flex items-start';
                    timeCell.innerHTML = `
            <span class="text-sm hidden sm:block">${formatTime(timeSlot.start)} - ${formatTime(timeSlot.end)}</span>
            <span class="text-xs sm:hidden">${timeSlot.start}-${timeSlot.end}</span>
        `;
                    row.appendChild(timeCell);

                    // Day columns
                    days.forEach(day => {
                        const cell = document.createElement('div');
                        cell.className = 'px-1 py-1 border-r border-gray-200 last:border-r-0 relative drop-zone min-h-[80px]';
                        cell.dataset.day = day;
                        cell.dataset.startTime = timeSlot.start;
                        cell.dataset.endTime = timeSlot.end;

                        // Find schedules that start EXACTLY in this time slot
                        const schedulesForSlot = schedules.filter(schedule => {
                            if (schedule.day_of_week !== day) return false;

                            const scheduleStart = schedule.start_time ? schedule.start_time.substring(0, 5) : '';
                            return scheduleStart === timeSlot.start;
                        });

                        if (schedulesForSlot.length > 0) {
                            const schedulesContainer = document.createElement('div');
                            schedulesContainer.className = 'space-y-1 h-full';

                            schedulesForSlot.forEach(schedule => {
                                const scheduleCard = createFlexibleScheduleCard(schedule);
                                schedulesContainer.appendChild(scheduleCard);
                            });

                            cell.appendChild(schedulesContainer);
                        } else {
                            // Check if this cell is occupied by a continuing schedule
                            const hasContinuingSchedule = schedules.some(schedule => {
                                if (schedule.day_of_week !== day) return false;

                                const scheduleStart = schedule.start_time ? schedule.start_time.substring(0, 5) : '';
                                const scheduleEnd = schedule.end_time ? schedule.end_time.substring(0, 5) : '';

                                if (!scheduleStart || !scheduleEnd) return false;

                                // Check if schedule spans through this time slot but doesn't start here
                                return (scheduleStart < timeSlot.start && scheduleEnd > timeSlot.start);
                            });

                            if (!hasContinuingSchedule) {
                                // Only show add button if cell is completely empty
                                const addButton = document.createElement('button');
                                addButton.innerHTML = '<i class="fas fa-plus text-sm"></i>';
                                addButton.className = 'w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print flex items-center justify-center p-2';
                                addButton.onclick = () => openAddModalForSlot(day, timeSlot.start, timeSlot.end);
                                cell.appendChild(addButton);
                            }
                        }

                        row.appendChild(cell);
                    });

                    manualGrid.appendChild(row);
                });

                initializeDragAndDrop();
            }

            // Generate dynamic time slots based on actual schedule times
            function generateDynamicTimeSlots(schedules) {
                const allTimes = new Set();

                // Add default time range
                const startHour = 7,
                    endHour = 21; // 7 AM to 9 PM
                for (let hour = startHour; hour <= endHour; hour++) {
                    for (let minute = 0; minute < 60; minute += 30) { // 30-minute intervals
                        if (hour === endHour && minute > 0) break;
                        const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                        allTimes.add(timeString);
                    }
                }

                // Add all schedule start and end times
                schedules.forEach(schedule => {
                    if (schedule.start_time) {
                        const startTime = schedule.start_time.substring(0, 5);
                        allTimes.add(startTime);
                    }
                    if (schedule.end_time) {
                        const endTime = schedule.end_time.substring(0, 5);
                        allTimes.add(endTime);
                    }
                });

                // Convert to array and sort
                const sortedTimes = Array.from(allTimes).sort((a, b) => {
                    const [aHours, aMinutes] = a.split(':').map(Number);
                    const [bHours, bMinutes] = b.split(':').map(Number);
                    return (aHours * 60 + aMinutes) - (bHours * 60 + bMinutes);
                });

                // Create time slots
                const timeSlots = [];
                for (let i = 0; i < sortedTimes.length - 1; i++) {
                    timeSlots.push({
                        start: sortedTimes[i],
                        end: sortedTimes[i + 1]
                    });
                }

                return timeSlots;
            }

            // Flexible schedule card that can be edited/moved regardless of time
            function createFlexibleScheduleCard(schedule) {
                const card = document.createElement('div');

                const colors = [
                    'bg-blue-100 border-blue-300 text-blue-800',
                    'bg-green-100 border-green-300 text-green-800',
                    'bg-purple-100 border-purple-300 text-purple-800',
                    'bg-orange-100 border-orange-300 text-orange-800',
                    'bg-pink-100 border-pink-300 text-pink-800'
                ];

                const colorIndex = schedule.schedule_id ?
                    (parseInt(schedule.schedule_id) % colors.length) :
                    Math.floor(Math.random() * colors.length);
                const colorClass = colors[colorIndex];

                card.className = `schedule-card ${colorClass} p-2 rounded-lg border-l-4 draggable cursor-move text-xs w-full`;
                card.draggable = true;
                card.dataset.scheduleId = schedule.schedule_id || '';
                card.dataset.yearLevel = schedule.year_level || '';
                card.dataset.sectionName = schedule.section_name || '';
                card.dataset.roomName = schedule.room_name || 'Online';
                card.dataset.startTime = schedule.start_time ? schedule.start_time.substring(0, 5) : '';
                card.dataset.endTime = schedule.end_time ? schedule.end_time.substring(0, 5) : '';

                card.innerHTML = `
        <div class="flex justify-between items-start mb-1">
            <div class="font-semibold truncate flex-1">
                ${schedule.course_code || ''}
            </div>
            <div class="flex space-x-1 flex-shrink-0 ml-1">
                <button onclick="event.stopPropagation(); editSchedule('${schedule.schedule_id || ''}')" class="text-yellow-600 hover:text-yellow-700 no-print">
                    <i class="fas fa-edit text-xs"></i>
                </button>
                <button onclick="event.stopPropagation(); openDeleteSingleModal(
                    '${schedule.schedule_id || ''}', 
                    '${schedule.course_code || ''}', 
                    '${schedule.section_name || ''}', 
                    '${schedule.day_of_week || ''}', 
                    '${schedule.start_time ? formatTime(schedule.start_time.substring(0, 5)) : ''}', 
                    '${schedule.end_time ? formatTime(schedule.end_time.substring(0, 5)) : ''}'
                )" class="text-red-600 hover:text-red-700 no-print">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
        </div>
        <div class="opacity-90 truncate">
            ${schedule.section_name || ''}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.faculty_name || ''}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.room_name || 'Online'}
        </div>
        <div class="font-medium mt-1 text-xs">
            ${schedule.start_time && schedule.end_time ? 
                `${schedule.start_time.substring(0, 5)} - ${schedule.end_time.substring(0, 5)}` : 
                ''}
        </div>
    `;

                return card;
            }

            // Generate time slots optimized for view display
            function generateViewTimeSlots(schedules) {
                const allTimes = new Set();

                // Base time slots every 30 minutes from 7:00 to 21:00
                for (let hour = 7; hour <= 21; hour++) {
                    for (let minute = 0; minute < 60; minute += 30) {
                        if (hour === 21 && minute > 0) break;
                        const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                        allTimes.add(timeString);
                    }
                }

                // Add schedule times to ensure they're included
                schedules.forEach(schedule => {
                    if (schedule.start_time) allTimes.add(schedule.start_time.substring(0, 5));
                    if (schedule.end_time) allTimes.add(schedule.end_time.substring(0, 5));
                });

                const sortedTimes = Array.from(allTimes).sort();
                const timeSlots = [];

                for (let i = 0; i < sortedTimes.length - 1; i++) {
                    timeSlots.push({
                        start: sortedTimes[i],
                        end: sortedTimes[i + 1]
                    });
                }

                return timeSlots;
            }
            // Schedule item for view tab
            function createViewScheduleItem(schedule, isExactStart = true) {
                const item = document.createElement('div');

                const colors = [
                    'bg-blue-100 border-blue-300 text-blue-800',
                    'bg-green-100 border-green-300 text-green-800',
                    'bg-purple-100 border-purple-300 text-purple-800',
                    'bg-orange-100 border-orange-300 text-orange-800',
                    'bg-pink-100 border-pink-300 text-pink-800'
                ];

                const colorIndex = schedule.schedule_id ?
                    (parseInt(schedule.schedule_id) % colors.length) :
                    Math.floor(Math.random() * colors.length);
                const colorClass = colors[colorIndex];

                // Apply reduced opacity for continuation schedules
                const opacityClass = isExactStart ? '' : 'opacity-70';

                item.className = `schedule-card ${colorClass} ${opacityClass} p-2 rounded-lg border-l-4 mb-1 schedule-item text-xs`;
                item.dataset.yearLevel = schedule.year_level || '';
                item.dataset.sectionName = schedule.section_name || '';
                item.dataset.roomName = schedule.room_name || 'Online';

                item.innerHTML = `
        <div class="font-semibold truncate mb-1">
            ${schedule.course_code || ''}
            ${!isExactStart ? '<span class="text-gray-500 text-xs">(cont.)</span>' : ''}
        </div>
        <div class="opacity-90 truncate mb-1">
            ${schedule.section_name || ''}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.faculty_name || ''}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.room_name || 'Online'}
        </div>
        ${isExactStart ? `
        <div class="font-medium mt-1 text-xs">
            ${schedule.start_time && schedule.end_time ? 
                `${schedule.start_time.substring(0, 5)} - ${schedule.end_time.substring(0, 5)}` : 
                ''}
        </div>
        ` : ''}
    `;

                return item;
            }

            // Debug function to check database state
            function verifyDeletion() {
                console.log("=== VERIFYING DELETION ===");
                console.log("Frontend schedule count:", window.scheduleData.length);

                // Make an API call to check backend state
                fetch("/chair/generate-schedules", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({
                            action: "get_schedule_count",
                            semester_id: window.currentSemester?.semester_id || "",
                            department_id: window.departmentId || ""
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Backend schedule count:", data.count);
                        console.log("Backend schedules sample:", data.schedules || []);

                        if (data.count > 0) {
                            console.warn("WARNING: Schedules still exist in database after deletion!");
                        } else {
                            console.log("SUCCESS: Database is properly cleared");
                        }
                    })
                    .catch(error => {
                        console.error("Verification error:", error);
                    });
            }
        </script>

        <!-- Include external JavaScript files -->
        <script src="/assets/js/generate_schedules.js"></script>
        <script src="/assets/js/manual_schedules.js"></script>
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