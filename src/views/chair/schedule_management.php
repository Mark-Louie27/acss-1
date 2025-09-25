<?php
ob_start();
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
                <div class="flex items-center space-x-4">
                    <!-- Print Options -->
                    <div class="relative">
                        <button id="printDropdownBtn" onclick="togglePrintDropdown()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i class="fas fa-print"></i>
                            <span>Print Options</span>
                            <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="printDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                            <div class="py-1">
                                <button onclick="printSchedule('all')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-calendar mr-2"></i>Print All Schedules
                                </button>
                                <button onclick="printSchedule('filtered')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-filter mr-2"></i>Print Filtered View
                                </button>
                                <button onclick="exportSchedule('excel')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-file-excel mr-2"></i>Export to Excel
                                </button>
                                <button onclick="exportSchedule('pdf')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-file-pdf mr-2"></i>Export to PDF
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
                            <select name="curriculum_id" id="curriculum_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white" required onchange="updateYearLevels()">
                                <option value="">Select Curriculum</option>
                                <?php foreach ($curricula as $curriculum): ?>
                                    <option value="<?php echo htmlspecialchars($curriculum['curriculum_id']); ?>">
                                        <?php echo htmlspecialchars($curriculum['curriculum_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Year Levels</label>
                            <select name="year_levels[]" id="year_levels" multiple class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white" size="4" onchange="updateSections()">
                                <option value="">Select Year Level</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sections</label>
                            <select name="sections[]" id="sections" multiple class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white" size="4" required>
                                <option value="">Select Section</option>
                            </select>
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

        <!-- Manual Edit Tab -->
        <div id="content-manual" class="tab-content <?php echo $activeTab !== 'manual' ? 'hidden' : ''; ?>">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-yellow-500 p-2 rounded-lg mr-3">
                            <i class="fas fa-edit text-white"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Manual Schedule Editor</h2>
                    </div>
                    <div class="flex items-center space-x-4 no-print">
                        <select id="filter-year-manual" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="filterSchedulesManual()">
                            <option value="">All Year Levels</option>
                            <?php $yearLevels = array_unique(array_column($schedules, 'year_level'));
                            foreach ($yearLevels as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-section-manual" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="filterSchedulesManual()">
                            <option value="">All Sections</option>
                            <?php $sectionNames = array_unique(array_column($schedules, 'section_name'));
                            foreach ($sectionNames as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-room-manual" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="filterSchedulesManual()">
                            <option value="">All Rooms</option>
                            <?php $rooms = array_unique(array_column($schedules, 'room_name'));
                            foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room ?? 'Online'); ?>"><?php echo htmlspecialchars($room ?? 'Online'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="add-schedule-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            <span>Add Schedule</span>
                        </button>
                        <button id="save-changes-btn" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors" onclick="saveAllChanges()">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                        <button id="delete-all-btn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors" onclick="deleteAllSchedules()">
                            <i class="fas fa-trash"></i>
                            <span>Delete All Schedules</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="min-w-full">
                        <!-- Header with days -->
                        <div class="grid grid-cols-7 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                            <div class="px-4 py-3 text-sm font-semibold text-gray-700 border-r border-gray-200 bg-gray-50">
                                Time
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Monday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Tuesday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Wednesday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Thursday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Friday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700">
                                Saturday
                            </div>
                        </div>

                        <!-- Time slots -->
                        <div id="schedule-grid" class="divide-y divide-gray-200">
                            <?php
                            $timeSlots = [
                                ['07:30', '08:30'],
                                ['08:30', '10:00'],
                                ['10:00', '11:00'],
                                ['11:00', '12:30'],
                                ['12:30', '13:30'],
                                ['13:00', '14:30'],
                                ['14:30', '15:30'],
                                ['15:30', '17:00'],
                                ['17:00', '18:00']
                            ];

                            $scheduleGrid = [];
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                            foreach ($schedules as $schedule) {
                                $day = $schedule['day_of_week'];
                                $startTime = substr($schedule['start_time'], 0, 5);
                                $endTime = substr($schedule['end_time'], 0, 5);

                                if (!isset($scheduleGrid[$day])) {
                                    $scheduleGrid[$day] = [];
                                }

                                if (!isset($scheduleGrid[$day][$startTime])) {
                                    $scheduleGrid[$day][$startTime] = [];
                                }
                                $scheduleGrid[$day][$startTime][] = $schedule;
                            }
                            ?>

                            <?php foreach ($timeSlots as $time): ?>
                                <?php
                                $duration = strtotime($time[1]) - strtotime($time[0]);
                                $rowSpan = $duration / 7200;
                                ?>
                                <div class="grid grid-cols-7 min-h-[<?php echo $rowSpan * 80; ?>px] hover:bg-gray-50 transition-colors duration-200" style="grid-row: span <?php echo $rowSpan; ?>;">
                                    <div class="px-4 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 flex items-center" rowspan="<?php echo $rowSpan; ?>">
                                        <span class="text-lg"><?php echo date('g:i A', strtotime($time[0])) . ' - ' . date('g:i A', strtotime($time[1])); ?></span>
                                    </div>
                                    <?php foreach ($days as $day): ?>
                                        <div class="px-2 py-2 border-r border-gray-200 last:border-r-0 min-h-[<?php echo $rowSpan * 80; ?>px] relative drop-zone"
                                            data-day="<?php echo $day; ?>"
                                            data-start-time="<?php echo $time[0]; ?>"
                                            data-end-time="<?php echo $time[1]; ?>">
                                            <?php
                                            $schedulesForSlot = isset($scheduleGrid[$day][$time[0]]) ? $scheduleGrid[$day][$time[0]] : [];
                                            foreach ($schedulesForSlot as $schedule) {
                                                $scheduleStart = substr($schedule['start_time'], 0, 5);
                                                $scheduleEnd = substr($schedule['end_time'], 0, 5);
                                                if ($scheduleStart === $time[0]) {
                                                    $colors = [
                                                        'bg-blue-100 border-blue-300 text-blue-800',
                                                        'bg-green-100 border-green-300 text-green-800',
                                                        'bg-purple-100 border-purple-300 text-purple-800',
                                                        'bg-orange-100 border-orange-300 text-orange-800',
                                                        'bg-pink-100 border-pink-300 text-pink-800'
                                                    ];
                                                    $colorClass = $colors[array_rand($colors)];
                                            ?>
                                                    <div class="schedule-card <?php echo $colorClass; ?> p-2 rounded-lg border-l-4 mb-1 draggable cursor-move"
                                                        draggable="true"
                                                        data-schedule-id="<?php echo $schedule['schedule_id']; ?>"
                                                        ondragstart="handleDragStart(event)"
                                                        ondragend="handleDragEnd(event)"
                                                        data-year-level="<?php echo htmlspecialchars($schedule['year_level']); ?>"
                                                        data-section-name="<?php echo htmlspecialchars($schedule['section_name']); ?>"
                                                        data-room-name="<?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>">
                                                        <div class="flex justify-between items-start mb-2">
                                                            <div class="font-semibold text-xs truncate mb-1">
                                                                <?php echo htmlspecialchars($schedule['course_code']); ?>
                                                            </div>
                                                            <button onclick="editSchedule('<?php echo $schedule['schedule_id']; ?>')" class="text-yellow-600 hover:text-yellow-700 text-xs no-print">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                        <div class="text-xs opacity-90 truncate mb-1">
                                                            <?php echo htmlspecialchars($schedule['section_name']); ?>
                                                        </div>
                                                        <div class="text-xs opacity-75 truncate">
                                                            <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                                        </div>
                                                        <div class="text-xs opacity-75 truncate">
                                                            <?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>
                                                        </div>
                                                        <div class="text-xs font-medium mt-1">
                                                            <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                            }
                                            if (empty($schedulesForSlot)) {
                                                ?>
                                                <button onclick="openAddModalForSlot('<?php echo $day; ?>', '<?php echo $time[0]; ?>', '<?php echo $time[1]; ?>')" class="w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print">
                                                    <i class="fas fa-plus text-lg"></i>
                                                </button>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Schedule Tab -->
        <div id="content-schedule" class="tab-content <?php echo $activeTab !== 'schedule-list' ? 'hidden' : ''; ?>">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <!-- Header with Filters -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-yellow-500 p-2 rounded-lg mr-3">
                                <i class="fas fa-calendar text-white"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Weekly Schedule View</h2>
                        </div>
                        <div class="flex items-center space-x-4 no-print">
                            <select id="filter-year" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="filterSchedules()">
                                <option value="">All Year Levels</option>
                                <?php $yearLevels = array_unique(array_column($schedules, 'year_level'));
                                foreach ($yearLevels as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filter-section" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="filterSchedules()">
                                <option value="">All Sections</option>
                                <?php $sectionNames = array_unique(array_column($schedules, 'section_name'));
                                foreach ($sectionNames as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filter-room" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="filterSchedules()">
                                <option value="">All Rooms</option>
                                <?php $rooms = array_unique(array_column($schedules, 'room_name'));
                                foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room ?? 'Online'); ?>"><?php echo htmlspecialchars($room ?? 'Online'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="delete-all-btn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors" onclick="deleteAllSchedules()">
                                <i class="fas fa-trash"></i>
                                <span>Delete All Schedules</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Weekly Timetable -->
                <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="min-w-full">
                        <!-- Header with days -->
                        <div class="grid grid-cols-7 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                            <div class="px-4 py-3 text-sm font-semibold text-gray-700 border-r border-gray-200 bg-gray-50">
                                Time
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Monday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Tuesday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Wednesday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Thursday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700 border-r border-gray-200">
                                Friday
                            </div>
                            <div class="px-4 py-3 text-sm font-semibold text-center text-gray-700">
                                Saturday
                            </div>
                        </div>

                        <!-- Time slots -->
                        <div id="timetableGrid" class="divide-y divide-gray-200">
                            <?php
                            $timeSlots = [
                                ['07:30', '08:30'],
                                ['08:30', '10:00'],
                                ['10:00', '11:00'],
                                ['11:00', '12:30'],
                                ['12:30', '13:30'],
                                ['13:00', '14:30'],
                                ['14:30', '15:30'],
                                ['15:30', '17:00'],
                                ['17:00', '18:00']
                            ];

                            $scheduleGrid = [];
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                            foreach ($schedules as $schedule) {
                                $day = $schedule['day_of_week'];
                                $startTime = substr($schedule['start_time'], 0, 5);
                                $endTime = substr($schedule['end_time'], 0, 5);

                                if (!isset($scheduleGrid[$day])) {
                                    $scheduleGrid[$day] = [];
                                }

                                if (!isset($scheduleGrid[$day][$startTime])) {
                                    $scheduleGrid[$day][$startTime] = [];
                                }
                                $scheduleGrid[$day][$startTime][] = $schedule;
                            }
                            ?>

                            <?php foreach ($timeSlots as $time): ?>
                                <?php
                                $duration = strtotime($time[1]) - strtotime($time[0]);
                                $rowSpan = $duration / 7200;
                                ?>
                                <div class="grid grid-cols-7 min-h-[<?php echo $rowSpan * 80; ?>px] hover:bg-gray-50 transition-colors duration-200" style="grid-row: span <?php echo $rowSpan; ?>;">
                                    <div class="px-4 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 flex items-center" rowspan="<?php echo $rowSpan; ?>">
                                        <span class="text-lg"><?php echo date('g:i A', strtotime($time[0])) . ' - ' . date('g:i A', strtotime($time[1])); ?></span>
                                    </div>
                                    <?php foreach ($days as $day): ?>
                                        <div class="px-2 py-2 border-r border-gray-200 last:border-r-0 min-h-[<?php echo $rowSpan * 80; ?>px] relative schedule-cell"
                                            data-day="<?php echo $day; ?>"
                                            data-start-time="<?php echo $time[0]; ?>"
                                            data-end-time="<?php echo $time[1]; ?>"
                                            data-year-level=""
                                            data-section-name=""
                                            data-room-name="">
                                            <?php
                                            $schedulesForSlot = isset($scheduleGrid[$day][$time[0]]) ? $scheduleGrid[$day][$time[0]] : [];
                                            foreach ($schedulesForSlot as $schedule) {
                                                $scheduleStart = substr($schedule['start_time'], 0, 5);
                                                $scheduleEnd = substr($schedule['end_time'], 0, 5);
                                                if ($scheduleStart === $time[0]) {
                                                    $colors = [
                                                        'bg-blue-100 border-blue-300 text-blue-800',
                                                        'bg-green-100 border-green-300 text-green-800',
                                                        'bg-purple-100 border-purple-300 text-purple-800',
                                                        'bg-orange-100 border-orange-300 text-orange-800',
                                                        'bg-pink-100 border-pink-300 text-pink-800'
                                                    ];
                                                    $colorClass = $colors[array_rand($colors)];
                                            ?>
                                                    <div class="schedule-card <?php echo $colorClass; ?> p-2 rounded-lg border-l-4 mb-1 schedule-item"
                                                        data-year-level="<?php echo htmlspecialchars($schedule['year_level']); ?>"
                                                        data-section-name="<?php echo htmlspecialchars($schedule['section_name']); ?>"
                                                        data-room-name="<?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>">
                                                        <div class="font-semibold text-xs truncate mb-1">
                                                            <?php echo htmlspecialchars($schedule['course_code']); ?>
                                                        </div>
                                                        <div class="text-xs opacity-90 truncate mb-1">
                                                            <?php echo htmlspecialchars($schedule['section_name']); ?>
                                                        </div>
                                                        <div class="text-xs opacity-75 truncate">
                                                            <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                                        </div>
                                                        <div class="text-xs opacity-75 truncate">
                                                            <?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>
                                                        </div>
                                                        <div class="text-xs font-medium mt-1">
                                                            <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?>
                                                        </div>
                                                    </div>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Overlay -->
            <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                <div class="bg-white p-8 rounded-lg shadow-xl text-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-yellow-500 mx-auto mb-4"></div>
                    <p class="text-gray-700 font-medium">Generating schedules...</p>
                </div>
            </div>

        </div>

        <!-- Enhanced Add/Edit Schedule Modal -->
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
                        <input type="text" id="course-code" name="course_code" list="course-codes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required oninput="syncCourseName()">
                        <datalist id="course-codes">
                            <?php foreach (array_unique(array_column($schedules, 'course_code')) as $code): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Course Name</label>
                        <input type="text" id="course-name" name="course_name" list="course-names" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required oninput="syncCourseCode()">
                        <datalist id="course-names">
                            <?php foreach (array_unique(array_column($schedules, 'course_name')) as $name): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Faculty</label>
                        <input type="text" id="faculty-name" name="faculty_name" list="faculty-names" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required>
                        <datalist id="faculty-names">
                            <?php foreach (array_unique(array_column($schedules, 'faculty_name')) as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Room</label>
                        <input type="text" id="room-name" name="room_name" list="room-names" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        <datalist id="room-names">
                            <?php foreach (array_unique(array_column(array_filter($schedules, fn($s) => $s['room_name']), 'room_name')) as $room): ?>
                                <option value="<?php echo htmlspecialchars($room); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Section</label>
                        <input type="text" id="section-name" name="section_name" list="section-names" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required>
                        <datalist id="section-names">
                            <?php foreach (array_unique(array_column($schedules, 'section_name')) as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time</label>
                            <select id="start-time" name="start_time_display" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="document.getElementById('modal-start-time').value=this.value">
                                <option value="07:30">7:30 AM</option>
                                <option value="08:30">8:30 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:30">12:30 PM</option>
                                <option value="13:30">1:30 PM</option>
                                <option value="14:30">2:30 PM</option>
                                <option value="15:30">3:30 PM</option>
                                <option value="17:00">5:00 PM</option>
                                <option value="18:00">6:00 PM</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Time</label>
                            <select id="end-time" name="end_time_display" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="document.getElementById('modal-end-time').value=this.value">
                                <option value="08:30">8:30 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:30">12:30 PM</option>
                                <option value="13:30">1:30 PM</option>
                                <option value="14:30">2:30 PM</option>
                                <option value="15:30">3:30 PM</option>
                                <option value="17:00">5:00 PM</option>
                                <option value="18:00">6:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Day</label>
                        <select id="day-select" name="day_select_display" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" onchange="document.getElementById('modal-day').value=this.value">
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            let scheduleData = <?php echo json_encode($schedules); ?> || [];
            let currentEditingId = null;
            let draggedElement = null;

            // Enhanced PHP data passing with validation support
            const jsData = <?php echo json_encode($jsData); ?>;
            const departmentId = jsData.departmentId;
            const currentSemester = jsData.currentSemester;
            const rawSectionsData = jsData.sectionsData || [];
            const currentAcademicYear = jsData.currentAcademicYear || "";
            const faculty = jsData.faculty || [];
            const classrooms = jsData.classrooms || [];
            const curricula = jsData.curricula || [];

            // Transform sections data
            const sectionsData = Array.isArray(rawSectionsData) ? rawSectionsData.map((s, index) => ({
                section_id: s.section_id ?? (index + 1),
                section_name: s.section_name ?? '',
                year_level: s.year_level ?? 'Unknown',
                academic_year: s.academic_year ?? '',
                current_students: s.current_students ?? 0,
                max_students: s.max_students ?? 30,
                semester: s.semester ?? '',
                is_active: s.is_active ?? 1
            })) : [];

            // Validation check on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Check if essential data is missing
                if (!departmentId) {
                    showValidationToast(['No department assigned to your account. Please contact administrator.']);
                } else if (!currentSemester) {
                    showValidationToast(['No active semester found. Please contact administrator to configure academic calendar.']);
                }

                // Initialize other functionality
                initializeDragAndDrop();
                const generateBtn = document.getElementById('generate-btn');
                if (generateBtn) generateBtn.addEventListener('click', generateSchedules);

                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                if (tab === 'schedule-list') switchTab('schedule');
                else if (tab === 'manual') switchTab('manual');
                else if (tab === 'generate') switchTab('generate');
            });

            // Add event listeners for real-time validation
            document.addEventListener('DOMContentLoaded', function() {
                // Clear validation errors when user starts selecting
                const curriculumSelect = document.getElementById('curriculum_id');
                if (curriculumSelect) {
                    curriculumSelect.addEventListener('change', function() {
                        if (this.value) {
                            highlightField('curriculum_id', false);
                        }
                    });
                }

                const yearLevelsSelect = document.getElementById('year_levels');
                if (yearLevelsSelect) {
                    yearLevelsSelect.addEventListener('change', function() {
                        if (this.selectedOptions.length > 0 && this.selectedOptions[0].value) {
                            highlightField('year_levels', false);
                        }
                    });
                }

                const sectionsSelect = document.getElementById('sections');
                if (sectionsSelect) {
                    sectionsSelect.addEventListener('change', function() {
                        if (this.selectedOptions.length > 0 && this.selectedOptions[0].value) {
                            highlightField('sections', false);
                        }
                    });
                }
            });

            // Tab switching
            function switchTab(tabName) {
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('bg-yellow-500', 'text-white');
                    btn.classList.add('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
                });
                document.getElementById(`tab-${tabName}`).classList.add('bg-yellow-500', 'text-white');
                document.getElementById(`tab-${tabName}`).classList.remove('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');

                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                document.getElementById(`content-${tabName}`).classList.remove('hidden');

                const url = new URL(window.location);
                url.searchParams.set('tab', tabName === 'schedule' ? 'schedule-list' : tabName);
                window.history.pushState({}, '', url);
            }

            // Drag and Drop functionality
            function handleDragStart(e) {
                draggedElement = e.target;
                e.target.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            }

            function handleDragEnd(e) {
                e.target.style.opacity = '1';
                draggedElement = null;
            }

            function handleDragOver(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            }

            function handleDragEnter(e) {
                if (e.target.classList.contains('drop-zone')) {
                    e.target.classList.add('bg-yellow-100', 'border-2', 'border-dashed', 'border-yellow-400');
                }
            }

            function handleDragLeave(e) {
                if (e.target.classList.contains('drop-zone')) {
                    e.target.classList.remove('bg-yellow-100', 'border-2', 'border-dashed', 'border-yellow-400');
                }
            }

            function handleDrop(e) {
                e.preventDefault();
                const dropZone = e.target.closest('.drop-zone');
                if (dropZone && draggedElement && dropZone !== draggedElement.parentElement) {
                    dropZone.classList.remove('bg-yellow-100', 'border-2', 'border-dashed', 'border-yellow-400');

                    const scheduleId = draggedElement.dataset.scheduleId;
                    const newDay = dropZone.dataset.day;
                    const newStartTime = dropZone.dataset.startTime;
                    const newEndTime = dropZone.dataset.endTime;

                    const scheduleIndex = scheduleData.findIndex(s => s.schedule_id == scheduleId);
                    if (scheduleIndex !== -1) {
                        scheduleData[scheduleIndex].day_of_week = newDay;
                        scheduleData[scheduleIndex].start_time = newStartTime + ':00';
                        scheduleData[scheduleIndex].end_time = newEndTime + ':00';
                    }

                    const oldButton = draggedElement.parentElement.querySelector('button');
                    if (oldButton) oldButton.style.display = 'block';

                    const existingCard = dropZone.querySelector('.schedule-card');
                    if (existingCard && existingCard !== draggedElement) {
                        draggedElement.parentElement.appendChild(existingCard);
                    }

                    const newButton = dropZone.querySelector('button');
                    if (newButton) newButton.style.display = 'none';

                    dropZone.appendChild(draggedElement);
                    showNotification('Schedule moved successfully! Don\'t forget to save changes.', 'success');
                }
            }

            function initializeDragAndDrop() {
                const dropZones = document.querySelectorAll('.drop-zone');
                dropZones.forEach(zone => {
                    zone.addEventListener('dragover', handleDragOver);
                    zone.addEventListener('dragenter', handleDragEnter);
                    zone.addEventListener('dragleave', handleDragLeave);
                    zone.addEventListener('drop', handleDrop);
                });
            }

            // Modal functions
            function openAddModal() {
                document.getElementById('modal-title').textContent = 'Add Schedule';
                const form = document.getElementById('schedule-form');
                form.reset();
                document.getElementById('schedule-id').value = '';
                document.getElementById('modal-day').value = '';
                document.getElementById('modal-start-time').value = '';
                document.getElementById('modal-end-time').value = '';
                document.getElementById('course-code').value = '';
                document.getElementById('course-name').value = '';
                document.getElementById('faculty-name').value = '';
                document.getElementById('room-name').value = '';
                document.getElementById('section-name').value = '';
                document.getElementById('start-time').value = '07:30';
                document.getElementById('day-select').value = 'Monday';
                document.getElementById('schedule-modal').classList.remove('hidden');
            }

            function openAddModalForSlot(day, startTime, endTime) {
                document.getElementById('modal-title').textContent = 'Add Schedule';
                const form = document.getElementById('schedule-form');
                form.reset();
                document.getElementById('schedule-id').value = '';
                document.getElementById('modal-day').value = day;
                document.getElementById('modal-start-time').value = startTime;
                document.getElementById('modal-end-time').value = endTime;
                document.getElementById('start-time').value = startTime;
                document.getElementById('day-select').value = day;
                document.getElementById('schedule-modal').classList.remove('hidden');
            }

            // Fixed editSchedule function
            function editSchedule(scheduleId) {
                console.log('Looking for scheduleId:', scheduleId);
                const schedule = scheduleData.find(s => String(s.schedule_id) === String(scheduleId));
                console.log('Found schedule:', schedule);

                if (!schedule) {
                    showNotification('Schedule not found', 'error');
                    return;
                }

                // Use existing modal instead of creating new one
                const modal = document.getElementById('schedule-modal');
                if (!modal) {
                    console.error('Modal element not found in DOM');
                    return;
                }

                // Update modal title
                document.getElementById('modal-title').textContent = 'Edit Schedule';

                // Populate form fields with schedule data
                document.getElementById('schedule-id').value = schedule.schedule_id;
                document.getElementById('course-code').value = schedule.course_code || '';
                document.getElementById('course-name').value = schedule.course_name || '';
                document.getElementById('faculty-name').value = schedule.faculty_name || '';
                document.getElementById('room-name').value = schedule.room_name || '';
                document.getElementById('section-name').value = schedule.section_name || '';

                // Set time fields
                const startTime = schedule.start_time.substring(0, 5);
                const endTime = schedule.end_time.substring(0, 5);
                document.getElementById('start-time').value = startTime;
                document.getElementById('end-time').value = endTime;
                document.getElementById('modal-start-time').value = startTime;
                document.getElementById('modal-end-time').value = endTime;

                // Set day field
                document.getElementById('day-select').value = schedule.day_of_week;
                document.getElementById('modal-day').value = schedule.day_of_week;

                // Show the modal
                showModal();
            }

            // Add this helper function to properly show the modal
            function showModal() {
                const modal = document.getElementById('schedule-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.style.display = 'flex';
                    modal.style.opacity = '1';
                    modal.style.visibility = 'visible';
                    modal.style.pointerEvents = 'auto';

                    // Focus on the first input for better UX
                    const firstInput = modal.querySelector('input[type="text"]');
                    if (firstInput) {
                        setTimeout(() => firstInput.focus(), 100);
                    }
                }
            }

            // Updated closeModal function
            function closeModal() {
                const modal = document.getElementById('schedule-modal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                    modal.style.opacity = '0';
                    modal.style.visibility = 'hidden';
                    modal.style.pointerEvents = 'none';

                    // Clear form
                    const form = document.getElementById('schedule-form');
                    if (form) {
                        form.reset();
                    }
                    document.getElementById('schedule-id').value = '';
                }
                currentEditingId = null;
            }

            // Updated openAddModal function for consistency
            function openAddModal() {
                document.getElementById('modal-title').textContent = 'Add Schedule';
                const form = document.getElementById('schedule-form');
                form.reset();

                // Clear all hidden fields
                document.getElementById('schedule-id').value = '';
                document.getElementById('modal-day').value = '';
                document.getElementById('modal-start-time').value = '';
                document.getElementById('modal-end-time').value = '';

                // Set default values
                document.getElementById('start-time').value = '07:30';
                document.getElementById('end-time').value = '08:30';
                document.getElementById('day-select').value = 'Monday';

                showModal();
            }

            // Updated openAddModalForSlot function
            function openAddModalForSlot(day, startTime, endTime) {
                document.getElementById('modal-title').textContent = 'Add Schedule';
                const form = document.getElementById('schedule-form');
                form.reset();

                document.getElementById('schedule-id').value = '';
                document.getElementById('modal-day').value = day;
                document.getElementById('modal-start-time').value = startTime;
                document.getElementById('modal-end-time').value = endTime;
                document.getElementById('start-time').value = startTime;
                document.getElementById('end-time').value = endTime;
                document.getElementById('day-select').value = day;

                showModal();
            }

            // Add click outside to close modal functionality
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('schedule-modal');
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            closeModal();
                        }
                    });
                }

                // Add ESC key to close modal
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });
            });

            // Sync course code and name
            function syncCourseName() {
                const code = document.getElementById('course-code').value;
                const name = scheduleData.find(s => s.course_code === code)?.course_name || '';
                document.getElementById('course-name').value = name;
            }

            function syncCourseCode() {
                const name = document.getElementById('course-name').value;
                const code = scheduleData.find(s => s.course_name === name)?.course_code || '';
                document.getElementById('course-code').value = code;
            }

            function closeModal() {
                const modal = document.getElementById('schedule-modal');
                modal.classList.add('hidden');
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.zIndex = '0';
                modal.style.pointerEvents = 'none';
                modal.style.height = '0';
                modal.style.overflow = 'hidden';
                currentEditingId = null;
            }

            function handleScheduleSubmit(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const scheduleId = formData.get('schedule_id');
                const endpoint = scheduleId ? '/chair/updateSchedule' : '/chair/addSchedule';
                const body = new URLSearchParams();

                if (scheduleId) {
                    body.append('schedule_id', scheduleId);
                    body.append('data', JSON.stringify(Object.fromEntries(formData)));
                } else {
                    body.append('data', JSON.stringify(Object.fromEntries(formData)));
                }

                fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: body
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(scheduleId ? 'Schedule updated successfully!' : 'Schedule added successfully!', 'success');
                            closeModal();
                            location.reload();
                        } else {
                            showNotification('Error: ' + (data.message || 'Failed to save schedule'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error saving schedule', 'error');
                    });
            }

            function saveAllChanges() {
                const formData = new FormData();
                formData.append('tab', 'manual');
                formData.append('schedules', JSON.stringify(scheduleData));

                fetch('/chair/schedule_management', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        showNotification('All changes saved successfully!', 'success');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error saving changes', 'error');
                    });
            }

            function filterSchedulesManual() {
                const yearLevel = document.getElementById('filter-year-manual').value;
                const section = document.getElementById('filter-section-manual').value;
                const room = document.getElementById('filter-room-manual').value;
                const scheduleItems = document.querySelectorAll('#schedule-grid .schedule-card');

                scheduleItems.forEach(item => {
                    const itemYearLevel = item.getAttribute('data-year-level');
                    const itemSectionName = item.getAttribute('data-section-name');
                    const itemRoomName = item.getAttribute('data-room-name');
                    const matchesYear = !yearLevel || itemYearLevel === yearLevel;
                    const matchesSection = !section || itemSectionName === section;
                    const matchesRoom = !room || itemRoomName === room;

                    item.parentElement.style.display = matchesYear && matchesSection && matchesRoom ? 'block' : 'none';
                });
            }

            // Updated generateSchedules function with validation
            function generateSchedules() {
                const form = document.getElementById('generate-form');
                const formData = new FormData(form);
                const curriculumId = formData.get('curriculum_id');
                const selectedYearLevels = formData.getAll('year_levels[]');
                const selectedSections = formData.getAll('sections[]');

                // Clear any existing error messages
                clearValidationErrors();

                // Validation checks
                const validationErrors = [];

                if (!curriculumId) {
                    validationErrors.push('Please select a curriculum');
                    highlightField('curriculum_id', true);
                }

                if (selectedYearLevels.length === 0) {
                    validationErrors.push('Please select at least one year level');
                    highlightField('year_levels', true);
                }

                if (selectedSections.length === 0) {
                    validationErrors.push('Please select at least one section');
                    highlightField('sections', true);
                }

                // Check if faculty data is available
                if (!faculty || faculty.length === 0) {
                    validationErrors.push('No faculty members available for assignment');
                }

                // Check if classroom data is available
                if (!classrooms || classrooms.length === 0) {
                    validationErrors.push('No classrooms available for assignment');
                }

                // If there are validation errors, show them and return
                if (validationErrors.length > 0) {
                    showValidationToast(validationErrors);
                    return;
                }

                // Clear any previous validation highlighting
                clearValidationErrors();

                // Show loading overlay
                document.getElementById('loading-overlay').classList.remove('hidden');

                const data = {
                    curriculum_id: curriculumId,
                    year_levels: selectedYearLevels,
                    sections: selectedSections,
                    semester_id: formData.get('semester_id'),
                    tab: 'generate'
                };

                fetch('/chair/generate-schedules', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams(data)
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        return response.text();
                    })
                    .then(text => {
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Invalid response format: ' + e.message);
                        }

                        document.getElementById('loading-overlay').classList.add('hidden');

                        if (data.success) {
                            scheduleData = data.schedules || [];

                            // Show success results
                            document.getElementById('generation-results').classList.remove('hidden');
                            document.getElementById('total-courses').textContent = data.schedules ? data.schedules.length : 0;
                            document.getElementById('total-sections').textContent = new Set(data.schedules?.map(s => s.section_name)).size || 0;

                            // Update success rate based on unassigned courses
                            const successRate = data.unassigned ? '95%' : '100%';
                            document.getElementById('success-rate').textContent = successRate;

                            updateScheduleDisplay(scheduleData);

                            // Show appropriate message based on completion
                            if (data.unassigned) {
                                showCompletionToast('warning', 'Schedules generated with some conflicts!', [
                                    'Some courses could not be automatically assigned',
                                    'Check for time conflicts or resource limitations',
                                    'You can manually adjust schedules in the Manual Edit tab'
                                ]);
                            } else {
                                showCompletionToast('success', 'Schedules generated successfully!', [
                                    `${data.schedules.length} courses scheduled`,
                                    `${new Set(data.schedules?.map(s => s.section_name)).size} sections assigned`,
                                    'All courses successfully scheduled without conflicts'
                                ]);
                            }
                        } else {
                            showValidationToast([data.message || 'Failed to generate schedules']);
                        }
                    })
                    .catch(error => {
                        document.getElementById('loading-overlay').classList.add('hidden');
                        console.error('Error:', error);
                        showValidationToast(['Error generating schedules: ' + error.message]);
                    });
            }

            // New function to show validation errors as toast
            function showValidationToast(errors) {
                const toastContainer = getOrCreateToastContainer();

                const toast = document.createElement('div');
                toast.className = 'validation-toast bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg mb-3 transform translate-x-full transition-transform duration-300';

                toast.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-3">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-semibold mb-2">Please fix the following issues:</h4>
                <ul class="text-sm space-y-1">
                    ${errors.map(error => `<li>• ${error}</li>`).join('')}
                </ul>
            </div>
            <button onclick="removeToast(this.parentElement.parentElement)" class="ml-3 text-white hover:text-red-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

                toastContainer.appendChild(toast);

                // Trigger animation
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);

                // Auto remove after 8 seconds
                setTimeout(() => {
                    removeToast(toast);
                }, 8000);
            }

            // New function to show completion messages
            function showCompletionToast(type, title, messages) {
                const toastContainer = getOrCreateToastContainer();

                const bgColor = type === 'success' ? 'bg-green-500' : 'bg-yellow-500';
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

                const toast = document.createElement('div');
                toast.className = `completion-toast ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-3 transform translate-x-full transition-transform duration-300`;

                toast.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-3">
                <i class="fas ${icon} text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-semibold mb-2">${title}</h4>
                <ul class="text-sm space-y-1">
                    ${messages.map(message => `<li>• ${message}</li>`).join('')}
                </ul>
            </div>
            <button onclick="removeToast(this.parentElement.parentElement)" class="ml-3 text-white hover:opacity-70">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

                toastContainer.appendChild(toast);

                // Trigger animation
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);

                // Auto remove after 10 seconds
                setTimeout(() => {
                    removeToast(toast);
                }, 10000);
            }

            // Helper function to get or create toast container
            function getOrCreateToastContainer() {
                let container = document.getElementById('toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container';
                    container.className = 'fixed top-4 right-4 z-50 max-w-md';
                    document.body.appendChild(container);
                }
                return container;
            }

            // Helper function to remove toast
            function removeToast(toast) {
                if (toast && toast.parentElement) {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.parentElement.removeChild(toast);
                        }
                    }, 300);
                }
            }

            // Helper function to highlight invalid fields
            function highlightField(fieldId, isError) {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (isError) {
                        field.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                        field.classList.remove('border-gray-300');
                    } else {
                        field.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                        field.classList.add('border-gray-300');
                    }
                }
            }

            // Helper function to clear validation errors
            function clearValidationErrors() {
                const fields = ['curriculum_id', 'year_levels', 'sections'];
                fields.forEach(fieldId => {
                    highlightField(fieldId, false);
                });

                // Remove any existing validation toasts
                const existingToasts = document.querySelectorAll('.validation-toast, .completion-toast');
                existingToasts.forEach(toast => {
                    removeToast(toast);
                });
            }

            function updateYearLevels() {
                const curriculumId = document.getElementById('curriculum_id').value;
                const yearLevelsSelect = document.getElementById('year_levels');
                yearLevelsSelect.innerHTML = '<option value="">Select Year Level</option>';

                if (curriculumId && Array.isArray(sectionsData)) {
                    const yearLevels = sectionsData
                        .filter(s => s.academic_year === currentAcademicYear && s.curriculum_id == curriculumId)
                        .map(s => s.year_level);

                    const uniqueYears = [...new Set(yearLevels.filter(y => y && y !== 'Unknown'))];
                    uniqueYears.sort((a, b) => {
                        const order = {
                            '1': 1,
                            '2': 2,
                            '3': 3,
                            '4': 4
                        };
                        return (order[a[0]] || 99) - (order[b[0]] || 99);
                    });

                    uniqueYears.forEach(year => {
                        const option = document.createElement('option');
                        option.value = year;
                        option.textContent = year;
                        yearLevelsSelect.appendChild(option);
                    });

                    for (let i = 0; i < yearLevelsSelect.options.length; i++) {
                        yearLevelsSelect.options[i].selected = true;
                    }
                    updateSections();
                }
            }

            function updateSections() {
                const curriculumId = document.getElementById('curriculum_id').value;
                const yearLevelsSelect = document.getElementById('year_levels');
                const selectedYears = Array.from(yearLevelsSelect.selectedOptions).map(opt => opt.value).filter(y => y);
                const sectionsSelect = document.getElementById('sections');
                sectionsSelect.innerHTML = '<option value="">Select Section</option>';

                if (curriculumId && Array.isArray(sectionsData)) {
                    let matchingSections = sectionsData.filter(s =>
                        s.academic_year === currentAcademicYear &&
                        s.curriculum_id == curriculumId
                    );

                    if (selectedYears.length > 0) {
                        matchingSections = matchingSections.filter(s => selectedYears.includes(s.year_level));
                    }

                    matchingSections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.section_id;
                        option.textContent = section.section_name;
                        sectionsSelect.appendChild(option);
                    });

                    for (let i = 0; i < sectionsSelect.options.length; i++) {
                        sectionsSelect.options[i].selected = true;
                    }
                }
            }

            // Enhanced debugging function that also tries to fix data issues
            function debugAndFixSectionsData() {
                console.group('=== SECTIONS DATA COMPREHENSIVE DEBUG ===');

                // Check raw data
                console.log('1. Raw sectionsData from PHP:', rawSectionsData);
                console.log('2. Processed sectionsData:', sectionsData);
                console.log('3. Current academic year:', currentAcademicYear);
                console.log('4. Available curricula:', curricula);
                console.log('5. Department ID:', departmentId);

                // If sectionsData is empty but rawSectionsData has content, use it
                if ((!Array.isArray(sectionsData) || sectionsData.length === 0) &&
                    Array.isArray(rawSectionsData) && rawSectionsData.length > 0) {
                    console.log('6. sectionsData is empty, copying from rawSectionsData...');
                    sectionsData = [...rawSectionsData];
                }

                // Check for data structure issues
                if (Array.isArray(sectionsData) && sectionsData.length > 0) {
                    console.log('7. Sample section structure:', sectionsData[0]);

                    // Check for curriculum_id issues
                    const curriculumIds = sectionsData.map(s => s.curriculum_id).filter(id => id);
                    const uniqueCurriculumIds = [...new Set(curriculumIds)];
                    console.log('8. Curriculum IDs in sections:', uniqueCurriculumIds);

                    // Check for academic year issues
                    const academicYears = sectionsData.map(s => s.academic_year).filter(year => year);
                    const uniqueAcademicYears = [...new Set(academicYears)];
                    console.log('9. Academic years in sections:', uniqueAcademicYears);

                    // Check for year level issues
                    const yearLevels = sectionsData.map(s => s.year_level).filter(level => level);
                    const uniqueYearLevels = [...new Set(yearLevels)];
                    console.log('10. Year levels in sections:', uniqueYearLevels);
                }

                console.groupEnd();
            }

            // New function to delete all schedules
            function deleteAllSchedules() {
                if (!scheduleData || scheduleData.length === 0) {
                    showNotification('No schedules to delete', 'info');
                    return;
                }

                // Show confirmation dialog
                const confirmMessage = `Are you sure you want to delete all ${scheduleData.length} schedules created today? This action cannot be undone.`;

                if (confirm(confirmMessage)) {
                    // Show loading state
                    const deleteBtn = document.getElementById('delete-all-btn');
                    const originalText = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
                    deleteBtn.disabled = true;

                    // Send delete request
                    fetch('/chair/delete-all-schedules', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                'confirm': 'true'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Clear local schedule data
                                scheduleData = [];

                                // Refresh the schedule display
                                updateScheduleDisplay([]);

                                // Show success message
                                showNotification(`Successfully deleted ${data.deleted_count || 'all'} schedules created today`, 'success');

                                // Hide generation results if visible
                                const generationResults = document.getElementById('generation-results');
                                if (generationResults) {
                                    generationResults.classList.add('hidden');
                                }
                            } else {
                                showNotification('Error deleting schedules: ' + (data.message || 'Unknown error'), 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting schedules:', error);
                            showNotification('Error deleting schedules. Please try again.', 'error');
                        })
                        .finally(() => {
                            // Restore button state
                            deleteBtn.innerHTML = originalText;
                            deleteBtn.disabled = false;
                        });
                }
            }

            // New function to delete individual schedule
            function deleteSchedule(scheduleId) {
                if (confirm('Are you sure you want to delete this schedule?')) {
                    // Remove from local data
                    const index = scheduleData.findIndex(s => String(s.schedule_id) === String(scheduleId));
                    if (index !== -1) {
                        scheduleData.splice(index, 1);

                        // Find and remove the schedule card from DOM
                        const scheduleCard = document.querySelector(`[data-schedule-id="${scheduleId}"]`);
                        if (scheduleCard) {
                            const parentCell = scheduleCard.closest('.drop-zone');
                            scheduleCard.remove();

                            // Show the "add" button if cell is now empty
                            if (!parentCell.querySelector('.schedule-card')) {
                                const day = parentCell.dataset.day;
                                const startTime = parentCell.dataset.startTime;
                                const endTime = parentCell.dataset.endTime;
                                parentCell.innerHTML = `<button onclick="openAddModalForSlot('${day}', '${startTime}', '${endTime}')" class="w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print">
                        <i class="fas fa-plus text-lg"></i>
                    </button>`;
                            }
                        }

                        showNotification('Schedule deleted successfully! Don\'t forget to save changes.', 'success');
                    }
                }
            }

            // Enhanced debugging function to check data structure
            function debugSectionsData() {
                console.group('Sections Data Debug');
                console.log('Raw sections data:', rawSectionsData);
                console.log('Processed sections data:', sectionsData);
                console.log('faculty data:', faculty);
                console.log('Current academic year:', currentAcademicYear);
                console.log('Available curricula:', curricula);

                if (Array.isArray(sectionsData)) {
                    console.log('Sections count:', sectionsData.length);
                    sectionsData.forEach((section, index) => {
                        console.log(`Section ${index}:`, {
                            id: section.section_id,
                            name: section.section_name,
                            year_level: section.year_level,
                            curriculum_id: section.curriculum_id,
                            academic_year: section.academic_year
                        });
                    });
                } else {
                    console.error('sectionsData is not an array:', typeof sectionsData);
                }
                console.groupEnd();
            }

            // Call debug function on page load (remove this in production)
            document.addEventListener('DOMContentLoaded', function() {
                // Add debugging
                debugSectionsData();

                // Rest of your existing DOMContentLoaded code...
                if (!departmentId) {
                    showValidationToast(['No department assigned to your account. Please contact administrator.']);
                } else if (!currentSemester) {
                    showValidationToast(['No active semester found. Please contact administrator to configure academic calendar.']);
                }

                initializeDragAndDrop();
                const generateBtn = document.getElementById('generate-btn');
                if (generateBtn) generateBtn.addEventListener('click', generateSchedules);

                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                if (tab === 'schedule-list') switchTab('schedule');
                else if (tab === 'manual') switchTab('manual');
                else if (tab === 'generate') switchTab('generate');
            });

            function updateScheduleDisplay(schedules) {
                scheduleData = schedules;
                const manualGrid = document.getElementById('schedule-grid');
                if (manualGrid) {
                    manualGrid.innerHTML = '';
                    const times = [
                        ['07:30', '08:30'],
                        ['08:30', '10:00'],
                        ['10:00', '11:00'],
                        ['11:00', '12:30'],
                        ['12:30', '13:30'],
                        ['13:00', '14:30'],
                        ['14:30', '15:30'],
                        ['15:30', '17:00'],
                        ['17:00', '18:00']
                    ];
                    times.forEach(time => {
                        const row = document.createElement('div');
                        row.className = 'grid grid-cols-7 min-h-[100px] hover:bg-gray-50';
                        row.innerHTML = `<div class="px-4 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-100 flex items-center">
                            ${formatTime(time[0])} - ${formatTime(time[1])}
                        </div>`;
                        ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].forEach(day => {
                            const cell = document.createElement('div');
                            cell.className = 'px-2 py-3 border-r border-gray-200 last:border-r-0 drop-zone relative';
                            cell.dataset.day = day;
                            cell.dataset.startTime = time[0];
                            cell.dataset.endTime = time[1];
                            const schedule = schedules.find(s =>
                                s.day_of_week === day &&
                                s.start_time.substring(0, 5) === time[0] &&
                                s.end_time.substring(0, 5) === time[1]
                            );
                            if (schedule) {
                                cell.innerHTML = `<div class="schedule-card bg-white border-l-4 border-yellow-500 rounded-lg p-3 shadow-sm draggable cursor-move" 
                                    draggable="true" data-schedule-id="${escapeHtml(schedule.schedule_id)}" ondragstart="handleDragStart(event)" ondragend="handleDragEnd(event)">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="font-semibold text-sm text-gray-900 truncate">${escapeHtml(schedule.course_code)}</div>
                                        <button onclick="editSchedule('${escapeHtml(schedule.schedule_id)}')" class="text-yellow-600 hover:text-yellow-700 text-xs no-print">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-600 truncate mb-1">${escapeHtml(schedule.course_name)}</div>
                                    <div class="text-xs text-gray-600 truncate mb-1">${escapeHtml(schedule.faculty_name)}</div>
                                    <div class="text-xs text-gray-600 truncate mb-2">${escapeHtml(schedule.room_name ?? 'Online')}</div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-medium text-yellow-600">${escapeHtml(schedule.section_name)}</span>
                                        <button onclick="deleteSchedule('${escapeHtml(schedule.schedule_id)}')" class="text-red-500 hover:text-red-700 text-xs no-print">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>`;
                            } else {
                                cell.innerHTML = `<button onclick="openAddModalForSlot('${day}', '${time[0]}', '${time[1]}')" class="w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print">
                                    <i class="fas fa-plus text-lg"></i>
                                </button>`;
                            }
                            row.appendChild(cell);
                        });
                        manualGrid.appendChild(row);
                    });
                    initializeDragAndDrop();
                }

                const viewGrid = document.getElementById('timetableGrid');
                if (viewGrid) {
                    viewGrid.innerHTML = '';
                    const times = [
                        ['07:30', '08:30'],
                        ['08:30', '10:00'],
                        ['10:00', '11:00'],
                        ['11:00', '12:30'],
                        ['12:30', '13:30'],
                        ['13:00', '14:30'],
                        ['14:30', '15:30'],
                        ['15:30', '17:00'],
                        ['17:00', '18:00']
                    ];
                    times.forEach(time => {
                        const row = document.createElement('div');
                        row.className = 'grid grid-cols-7 min-h-[100px] hover:bg-gray-50';
                        row.innerHTML = `<div class="px-4 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-100 flex items-center">
                            ${formatTime(time[0])} - ${formatTime(time[1])}
                        </div>`;
                        ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].forEach(day => {
                            const cell = document.createElement('div');
                            cell.className = 'px-2 py-3 border-r border-gray-200 last:border-r-0 schedule-cell';
                            const daySchedules = schedules.filter(s =>
                                s.day_of_week === day &&
                                s.start_time.substring(0, 5) === time[0] &&
                                s.end_time.substring(0, 5) === time[1]
                            );
                            if (daySchedules.length > 0) {
                                daySchedules.forEach(schedule => {
                                    const colorClass = ['bg-blue-100', 'bg-green-100', 'bg-purple-100', 'bg-orange-100', 'bg-pink-100'][Math.floor(Math.random() * 5)] + ' border-l-4';
                                    cell.innerHTML += `<div class="schedule-card ${colorClass} p-2 rounded-lg mb-1 schedule-item" 
                                        data-year-level="${escapeHtml(schedule.year_level)}" 
                                        data-section-name="${escapeHtml(schedule.section_name)}" 
                                        data-room-name="${escapeHtml(schedule.room_name ?? 'Online')}">
                                        <div class="font-semibold text-xs truncate mb-1">${escapeHtml(schedule.course_code)}</div>
                                        <div class="text-xs opacity-90 truncate mb-1">${escapeHtml(schedule.section_name)}</div>
                                        <div class="text-xs opacity-75 truncate">${escapeHtml(schedule.faculty_name)}</div>
                                        <div class="text-xs opacity-75 truncate">${escapeHtml(schedule.room_name ?? 'Online')}</div>
                                    </div>`;
                                });
                            }
                            row.appendChild(cell);
                        });
                        viewGrid.appendChild(row);
                    });
                }
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
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
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

                    cell.style.display = shouldShow ? 'block' : 'block'; // Keep cell visible if any item matches
                });
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

            // Enhanced print functionality
            function togglePrintDropdown() {
                const dropdown = document.getElementById('printDropdown');
                dropdown.classList.toggle('hidden');
            }

            function printSchedule(type) {
                // Hide dropdown
                document.getElementById('printDropdown').classList.add('hidden');

                if (type === 'filtered') {
                    // Apply current filters before printing
                    filterSchedules();
                } else if (type === 'all') {
                    // Clear all filters to show everything
                    clearFilters();
                }

                // Switch to schedule view for printing
                switchTab('schedule');

                // Print after a short delay to ensure tab switch completes
                setTimeout(() => {
                    window.print();
                }, 100);
            }

            function exportSchedule(format) {
                // Hide dropdown
                document.getElementById('printDropdown').classList.add('hidden');

                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'download';
                form.appendChild(actionInput);

                const formatInput = document.createElement('input');
                formatInput.name = 'format';
                formatInput.value = format;
                form.appendChild(formatInput);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }

            function confirmPrint() {
                if (confirm("Are you sure you want to print the schedule? This will open the print dialog.")) {
                    window.print();
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                initializeDragAndDrop();
                const generateBtn = document.getElementById('generate-btn');
                if (generateBtn) generateBtn.addEventListener('click', generateSchedules);

                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                if (tab === 'schedule-list') switchTab('schedule');
                else if (tab === 'manual') switchTab('manual');
                else if (tab === 'generate') switchTab('generate');
            });
        </script>

        <?php
        $content = ob_get_clean();
        require_once __DIR__ . '/layout.php';
        ?>