<?php
ob_start();
?>

<div class="min-h-screen bg-gray-100 font-sans">
    <div class="container mx-auto p-6">
        <!-- Header -->
        <h1 class="text-3xl font-semibold text-gray-900 mb-6">Schedule Management System</h1>

        <!-- Notifications -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-6 shadow-md">
                <?php echo nl2br(htmlspecialchars($error)); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6 shadow-md">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Semester Info -->
        <div class="bg-white p-4 rounded-md shadow-md mb-6 flex items-center justify-between">
            <div class="flex items-center">
                <i class="far fa-calendar-alt text-blue-500 mr-2"></i>
                <span class="text-gray-700 font-medium">Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?></span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="?tab=manual" class="tab-link <?php echo $activeTab === 'manual' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> inline-flex items-center px-4 py-2 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-calendar-plus mr-2"></i> Manual Edit
                    </a>
                    <a href="?tab=generate" class="tab-link <?php echo $activeTab === 'generate' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> inline-flex items-center px-4 py-2 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-magic mr-2"></i> Generate Schedules
                    </a>
                    <a href="?tab=schedule-list" class="tab-link <?php echo $activeTab === 'schedule-list' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> inline-flex items-center px-4 py-2 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-list mr-2"></i> Schedule List
                    </a>
                    <a href="?tab=export" class="tab-link <?php echo $activeTab === 'export' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> inline-flex items-center px-4 py-2 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-file-export mr-2"></i> Export Template
                    </a>
                </nav>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Manual Edit Tab -->
            <div class="tab-pane <?php echo $activeTab === 'manual' ? 'active' : 'hidden'; ?>" id="manual" role="tabpanel">
                <div class="bg-white rounded-md shadow-md p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Manual Schedule Editor</h3>
                    <div class="schedule-table-container overflow-x-auto">
                        <table class="w-full schedule-table border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Time</th>
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Monday</th>
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Tuesday</th>
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Wednesday</th>
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Thursday</th>
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Friday</th>
                                    <th class="border-b border-gray-200 p-3 text-left text-sm font-medium text-gray-700">Saturday</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleBody">
                                <?php
                                $times = [
                                    ['07:30', '08:30'],
                                    ['08:30', '09:30'],
                                    ['09:30', '10:30'],
                                    ['10:30', '11:30'],
                                    ['11:30', '12:30'],
                                    ['12:30', '13:30'],
                                    ['13:30', '14:30'],
                                    ['14:30', '15:30'],
                                    ['15:30', '16:30'],
                                    ['16:30', '17:30'],
                                    ['17:30', '18:00']
                                ];
                                foreach ($times as $time) {
                                    echo "<tr>";
                                    echo "<td class='border-b border-gray-200 p-2 text-sm text-gray-700 bg-gray-50'>{$time[0]} - {$time[1]}</td>";
                                    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
                                        $schedule = array_filter($schedules, fn($s) => $s['day_of_week'] === $day && $s['start_time'] === $time[0]);
                                        $schedule = reset($schedule) ?: null;
                                        echo "<td class='droppable border-b border-gray-200 p-2 text-sm text-gray-700 min-h-[60px] relative' data-time='{$time[0]}' data-end-time='{$time[1]}' data-day='{$day}'>";
                                        if ($schedule) {
                                            echo "<div class='schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative' data-id='{$schedule['schedule_id']}'>";
                                            echo "<strong class='text-gray-900'>{$schedule['course_code']} - {$schedule['course_name']}</strong><br>";
                                            echo "<span class='text-gray-600 text-xs'>{$schedule['faculty_name']}</span><br>";
                                            echo "<span class='text-gray-600 text-xs'>" . (isset($schedule['room_name']) ? $schedule['room_name'] : 'Online') . " </span><br>";
                                            echo "<span class='text-gray-600 text-xs'>{$schedule['section_name']} ({$schedule['schedule_type']})</span>";
                                            echo "<div class='absolute top-1 right-1 flex space-x-1'>";
                                            echo "<button class='remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded' onclick='removeSchedule(this)'>✕</button>";
                                            echo "<button class='edit-btn text-blue-500 text-xs p-1 hover:bg-blue-100 rounded' onclick='editSchedule(this)'>✎</button>";
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                        echo "</td>";
                                    }
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <form id="scheduleForm" method="POST" action="" class="mt-6">
                        <input type="hidden" name="tab" value="manual">
                        <input type="hidden" name="schedules" id="schedulesInput">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Generate Schedules Tab -->
            <div class="tab-pane <?php echo $activeTab === 'generate' ? 'active' : 'hidden'; ?>" id="generate" role="tabpanel">
                <div class="bg-white rounded-md shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Generate Schedules</h3>
                    <form method="POST" class="grid grid-cols-1 gap-6">
                        <input type="hidden" name="tab" value="generate">
                        <div>
                            <label for="generate_curriculum_id" class="block text-sm font-medium text-gray-700">Curriculum</label>
                            <select name="curriculum_id" id="generate_curriculum_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select Curriculum</option>
                                <?php foreach ($curricula as $curriculum): ?>
                                    <option value="<?php echo htmlspecialchars($curriculum['curriculum_id']); ?>">
                                        <?php echo htmlspecialchars($curriculum['curriculum_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?></label>
                            <input type="hidden" name="semester_id" value="<?php echo htmlspecialchars($currentSemester['semester_id'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="year_levels" class="block text-sm font-medium text-gray-700">Year Levels</label>
                            <select name="year_levels[]" id="year_levels" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" multiple>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                Generate Schedules
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Schedule List Tab -->
            <!-- Schedule List Tab -->
            <div class="tab-pane <?php echo $activeTab === 'schedule-list' ? 'active' : 'hidden'; ?>" id="schedule-list" role="tabpanel">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Weekly Schedule</h3>
                        <div class="flex space-x-3">
                            <select id="filterYearLevel" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm" onchange="filterSchedules()">
                                <option value="">All Year Levels</option>
                                <?php
                                $yearLevels = array_unique(array_column($schedules, 'year_level'));
                                foreach ($yearLevels as $year) {
                                    echo "<option value='" . htmlspecialchars($year) . "'>" . htmlspecialchars($year) . "</option>";
                                }
                                ?>
                            </select>
                            <select id="filterSection" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm" onchange="filterSchedules()">
                                <option value="">All Sections</option>
                                <?php
                                $sections = array_unique(array_column($schedules, 'section_name'));
                                foreach ($sections as $section) {
                                    echo "<option value='" . htmlspecialchars($section) . "'>" . htmlspecialchars($section) . "</option>";
                                }
                                ?>
                            </select>
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
                                // Generate time slots from 7:00 AM to 6:00 PM
                                $timeSlots = [];
                                for ($hour = 7; $hour <= 18; $hour++) {
                                    $timeSlots[] = sprintf("%02d:00", $hour);
                                }

                                // Group schedules by day and time
                                $scheduleGrid = [];
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                                foreach ($schedules as $schedule) {
                                    $day = $schedule['day_of_week'];
                                    $startTime = date('H:i', strtotime($schedule['start_time']));
                                    $endTime = date('H:i', strtotime($schedule['end_time']));

                                    if (!isset($scheduleGrid[$day])) {
                                        $scheduleGrid[$day] = [];
                                    }

                                    // Find which time slots this schedule spans
                                    $startHour = (int)date('H', strtotime($schedule['start_time']));
                                    $endHour = (int)date('H', strtotime($schedule['end_time']));

                                    for ($h = $startHour; $h < $endHour; $h++) {
                                        $timeKey = sprintf("%02d:00", $h);
                                        if (!isset($scheduleGrid[$day][$timeKey])) {
                                            $scheduleGrid[$day][$timeKey] = [];
                                        }
                                        $scheduleGrid[$day][$timeKey][] = $schedule;
                                    }
                                }
                                ?>

                                <?php foreach ($timeSlots as $time): ?>
                                    <div class="grid grid-cols-6 min-h-[80px] hover:bg-gray-50 transition-colors duration-200">
                                        <!-- Time column -->
                                        <div class="px-4 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 flex items-center">
                                            <span class="text-lg"><?php echo date('g:i A', strtotime($time)); ?></span>
                                        </div>

                                        <!-- Day columns -->
                                        <?php foreach ($days as $day): ?>
                                            <div class="px-2 py-2 border-r border-gray-200 last:border-r-0 min-h-[80px] relative">
                                                <?php
                                                if (isset($scheduleGrid[$day][$time])) {
                                                    foreach ($scheduleGrid[$day][$time] as $schedule) {
                                                        $colors = [
                                                            'bg-blue-100 border-blue-300 text-blue-800',
                                                            'bg-green-100 border-green-300 text-green-800',
                                                            'bg-purple-100 border-purple-300 text-purple-800',
                                                            'bg-orange-100 border-orange-300 text-orange-800',
                                                            'bg-pink-100 border-pink-300 text-pink-800'
                                                        ];
                                                        $colorClass = $colors[array_rand($colors)];
                                                ?>
                                                        <div class="<?php echo $colorClass; ?> p-2 rounded-lg border-l-4 mb-1 cursor-pointer hover:shadow-md transition-shadow duration-200 schedule-item"
                                                            data-schedule-id="<?php echo $schedule['schedule_id']; ?>"
                                                            onclick="showScheduleDetails(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
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

                    <!-- Legend -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Legend</h4>
                        <div class="flex flex-wrap gap-4 text-xs">
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 bg-blue-100 border-l-4 border-blue-300 rounded"></div>
                                <span class="text-gray-600">Click on any schedule item to view details</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 bg-green-100 border-l-4 border-green-300 rounded"></div>
                                <span class="text-gray-600">Different colors for visual distinction</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Details Modal -->
            <div id="scheduleModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 99999 !important;">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Schedule Details</h3>
                        <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="scheduleDetails" class="space-y-3">
                        <!-- Schedule details will be populated here -->
                    </div>
                    <div class="mt-6 flex space-x-3">
                        <button onclick="editScheduleFromModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200">
                            Edit Schedule
                        </button>
                        <button onclick="deleteScheduleFromModal()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200">
                            Delete Schedule
                        </button>
                    </div>
                </div>
            </div>

            <!-- Export Template Tab -->
            <div class="tab-pane <?php echo $activeTab === 'export' ? 'active' : 'hidden'; ?>" id="export" role="tabpanel">
                <div class="bg-white rounded-md shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Export Schedule Template</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="tab" value="export">
                        <p class="text-gray-600">Export a plain Excel file containing all available resources (curricula, courses, faculty, rooms, sections) for manual editing.</p>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            Export Template
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSchedule = null;

    function showScheduleDetails(schedule) {
        currentSchedule = schedule;

        // Create modal if it doesn't exist in body
        let modal = document.getElementById('scheduleModal');
        if (!modal.parentElement.tagName === 'BODY') {
            // Remove existing modal
            modal.remove();

            // Create new modal at body level
            const modalHTML = `
            <div id="scheduleModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" style="z-index: 99999 !important;">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Schedule Details</h3>
                        <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="scheduleDetails" class="space-y-3">
                        <!-- Schedule details will be populated here -->
                    </div>
                    <div class="mt-6 flex space-x-3">
                        <button onclick="editScheduleFromModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200">
                            Edit Schedule
                        </button>
                        <button onclick="deleteScheduleFromModal()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200">
                            Delete Schedule
                        </button>
                    </div>
                </div>
            </div>
        `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('scheduleModal');

            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeScheduleModal();
                }
            });
        }

        const detailsDiv = document.getElementById('scheduleDetails');

        detailsDiv.innerHTML = `
        <div class="space-y-2">
            <div><span class="font-medium">Course:</span> ${schedule.course_code} - ${schedule.course_name}</div>
            <div><span class="font-medium">Section:</span> ${schedule.section_name}</div>
            <div><span class="font-medium">Year Level:</span> ${schedule.year_level}</div>
            <div><span class="font-medium">Day:</span> ${schedule.day_of_week}</div>
            <div><span class="font-medium">Time:</span> ${schedule.start_time} - ${schedule.end_time}</div>
            <div><span class="font-medium">Faculty:</span> ${schedule.faculty_name}</div>
            <div><span class="font-medium">Room:</span> ${schedule.room_name || 'Online'}</div>
        </div>
    `;

        modal.classList.remove('hidden');
    }

    function closeScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        currentSchedule = null;
    }

    function editScheduleFromModal() {
        if (currentSchedule) {
            editSchedule(currentSchedule);
            closeScheduleModal();
        }
    }

    function deleteScheduleFromModal() {
        if (currentSchedule) {
            deleteSchedule(currentSchedule.schedule_id);
            closeScheduleModal();
        }
    }

    function filterSchedules() {
        const yearLevel = document.getElementById('filterYearLevel').value;
        const section = document.getElementById('filterSection').value;

        const scheduleItems = document.querySelectorAll('.schedule-item');

        scheduleItems.forEach(item => {
            const scheduleData = JSON.parse(item.getAttribute('onclick').match(/showScheduleDetails\((.*?)\)/)[1]);

            let show = true;

            if (yearLevel && scheduleData.year_level !== yearLevel) {
                show = false;
            }

            if (section && scheduleData.section_name !== section) {
                show = false;
            }

            if (show) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Close modal when clicking outside
    document.getElementById('scheduleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeScheduleModal();
        }
    });
    const departmentId = <?php echo json_encode($departmentId); ?>;
    const times = [
        ['07:30', '08:30'],
        ['08:30', '09:30'],
        ['09:30', '10:30'],
        ['10:30', '11:30'],
        ['11:30', '12:30'],
        ['12:30', '13:30'],
        ['13:30', '14:30'],
        ['14:30', '15:30'],
        ['15:30', '16:30'],
        ['16:30', '17:30'],
        ['17:30', '18:00']
    ];
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    let scheduleData = <?php echo json_encode($schedules); ?> || [];

    function populateSchedule() {
        const tbody = document.getElementById('scheduleBody');
        times.forEach(time => {
            const row = document.createElement('tr');
            const timeCell = document.createElement('td');
            timeCell.textContent = `${time[0]} - ${time[1]}`;
            timeCell.classList.add('border-b', 'border-gray-200', 'p-2', 'text-sm', 'text-gray-700', 'bg-gray-50');
            row.appendChild(timeCell);
            days.forEach(day => {
                const cell = document.createElement('td');
                cell.dataset.time = time[0];
                cell.dataset.endTime = time[1];
                cell.dataset.day = day;
                cell.classList.add('droppable', 'border-b', 'border-gray-200', 'p-2', 'text-sm', 'text-gray-700', 'min-h-[60px]', 'relative');
                row.appendChild(cell);
            });
            tbody.appendChild(row);
        });
        renderSchedules();
    }

    function renderSchedules() {
        document.querySelectorAll('.droppable').forEach(cell => {
            cell.innerHTML = '';
            const schedule = scheduleData.find(s => s.day_of_week === cell.dataset.day && s.start_time === cell.dataset.time);
            if (schedule) {
                cell.innerHTML = `<div class="schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative" data-id="${schedule.schedule_id ?? ''}"><strong class="text-gray-900">${schedule.course_code} - ${schedule.course_name}</strong><br><span class="text-gray-600 text-xs">${schedule.faculty_name}</span><br><span class="text-gray-600 text-xs">${schedule.room_name}</span><br><span class="text-gray-600 text-xs">${schedule.section_name} (${schedule.schedule_type})</span><div class="absolute top-1 right-1 flex space-x-1"><button class="remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded" onclick="removeSchedule(this)">✕</button><button class="edit-btn text-blue-500 text-xs p-1 hover:bg-blue-100 rounded" onclick="editSchedule(this)">✎</button></div></div>`;
            }
        });
    }

    function filterSchedules() {
        const yearLevel = document.getElementById('filterYearLevel').value;
        const section = document.getElementById('filterSection').value;
        const rows = document.querySelectorAll('#scheduleList tr');
        rows.forEach(row => {
            if (row.cells.length > 2) {
                const rowYearLevel = row.cells[2].textContent;
                const rowSection = row.cells[1].textContent;
                const show = (!yearLevel || rowYearLevel === yearLevel) && (!section || rowSection === section);
                row.style.display = show ? '' : 'none';
            }
        });
    }

    function deleteSchedule(scheduleId) {
        if (confirm('Are you sure you want to delete this schedule?')) {
            fetch('/chair/deleteSchedule', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `schedule_id=${scheduleId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`#scheduleList tr td button[onclick="deleteSchedule(${scheduleId})"]`).closest('tr').remove();
                        alert('Schedule deleted successfully.');
                    } else {
                        alert('Failed to delete schedule.');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }

    function editSchedule(button) {
        const item = button.parentElement.parentElement;
        const schedule = scheduleData.find(s => s.schedule_id === item.dataset.id) || JSON.parse(item.dataset.schedule);
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Schedule</h3>
                <form id="editForm">
                    <input type="hidden" name="schedule_id" value="${schedule.schedule_id}">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Course</label>
                        <select name="course_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="${schedule.course_id}">${schedule.course_code} - ${schedule.course_name}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Section</label>
                        <select name="section_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="${schedule.section_id}">${schedule.section_name}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Room</label>
                        <select name="room_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="${schedule.room_id ?? ''}">${schedule.room_name ?? 'Online'}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Faculty</label>
                        <select name="faculty_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="${schedule.faculty_id}">${schedule.faculty_name}</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Day</label>
                        <select name="day_of_week" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            ${days.map(d => `<option value="${d}" ${d === schedule.day_of_week ? 'selected' : ''}>${d}</option>`).join('')}
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Start Time</label>
                        <select name="start_time" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            ${times.map(t => `<option value="${t[0]}" ${t[0] === schedule.start_time ? 'selected' : ''}>${t[0]} - ${t[1]}</option>`).join('')}
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors duration-200" onclick="this.parentElement.parentElement.parentElement.remove()">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">Save Changes</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        document.getElementById('editForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('/chair/updateSchedule', {
                    method: 'POST',
                    body: new URLSearchParams({
                        schedule_id: formData.get('schedule_id'),
                        data: JSON.stringify(Object.fromEntries(formData))
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Schedule updated successfully.');
                        modal.remove();
                        location.reload();
                    } else {
                        alert('Failed to update schedule.');
                    }
                })
                .catch(error => console.error('Error:', error));
        };
    }

    document.addEventListener('dragover', e => {
        e.preventDefault();
        const cell = e.target.closest('.droppable');
        if (cell) cell.classList.add('bg-gray-100');
    });

    document.addEventListener('dragleave', e => {
        const cell = e.target.closest('.droppable');
        if (cell) cell.classList.remove('bg-gray-100');
    });

    document.addEventListener('drop', e => {
        e.preventDefault();
        const cell = e.target.closest('.droppable');
        if (!cell || !document.getElementById('draggableBox').draggable) return;

        cell.classList.remove('bg-gray-100');
        const curriculumId = document.getElementById('curriculum_id').value;
        const facultyId = document.getElementById('faculty_id').value;
        const courseId = document.getElementById('course_id').value;
        const roomId = document.getElementById('room_id').value;
        const sectionId = document.getElementById('section_id').value;

        if (curriculumId && facultyId && courseId && roomId && sectionId) {
            const courseText = document.getElementById('course_id').options[document.getElementById('course_id').selectedIndex].text;
            const facultyText = document.getElementById('faculty_id').options[document.getElementById('faculty_id').selectedIndex].text;
            const roomText = document.getElementById('room_id').options[document.getElementById('room_id').selectedIndex].text;
            const sectionText = document.getElementById('section_id').options[document.getElementById('section_id').selectedIndex].text;
            cell.innerHTML = `<div class="schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative" data-id=""><strong class="text-gray-900">${courseText}</strong><br><span class="text-gray-600 text-xs">${facultyText}</span><br><span class="text-gray-600 text-xs">${roomText}</span><br><span class="text-gray-600 text-xs">${sectionText}</span><div class="absolute top-1 right-1 flex space-x-1"><button class="remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded" onclick="removeSchedule(this)">✕</button><button class="edit-btn text-blue-500 text-xs p-1 hover:bg-blue-100 rounded" onclick="editSchedule(this)">✎</button></div></div>`;
            scheduleData.push({
                curriculum_id: curriculumId,
                course_id: courseId,
                faculty_id: facultyId,
                room_id: roomId,
                section_id: sectionId,
                day_of_week: cell.dataset.day,
                start_time: cell.dataset.time,
                end_time: cell.dataset.endTime
            });
            document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
        } else {
            alert('Please select all fields.');
        }
    });

    function removeSchedule(button) {
        const item = button.parentElement.parentElement;
        item.remove();
        scheduleData = scheduleData.filter(s => s.schedule_id !== item.dataset.id);
        document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
    }

    if (document.getElementById('scheduleBody').children.length === 0) {
        populateSchedule();
    }
    if (document.getElementById('scheduleList')) {
        filterSchedules();
    }
</script>

<style>
    .container {
        max-width: 1200px;
    }

    .schedule-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .schedule-table th,
    .schedule-table td {
        vertical-align: top;
    }

    .droppable:hover {
        background-color: #f3f4f6;
        transition: background-color 0.2s;
    }

    .schedule-item {
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .schedule-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .tab-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .schedule-table {
            display: block;
            overflow-x: auto;
        }

        .schedule-table thead,
        .schedule-table tbody,
        .schedule-table th,
        .schedule-table td {
            display: block;
        }

        .schedule-table tr {
            display: flex;
            flex-direction: column;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
        }

        .schedule-table td {
            padding: 0.5rem;
            border: none;
        }

        .schedule-table td:before {
            content: attr(data-label);
            font-weight: bold;
            margin-right: 0.5rem;
        }

        .schedule-table td[data-label="Time"]:before {
            content: "Time: ";
        }

        .schedule-table td[data-label]:not(:first-child):before {
            content: attr(data-label) ": ";
        }
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            margin: 0;
        }

        body * {
            visibility: hidden;
        }

        .schedule-table,
        .schedule-table * {
            visibility: visible;
        }

        .schedule-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .schedule-table th,
        .schedule-table td {
            font-size: 10px;
            padding: 5px;
        }

        .schedule-item button {
            display: none;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>