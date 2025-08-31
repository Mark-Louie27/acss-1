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
                                    ['07:30', '08:30'], // 60 minutes
                                    ['08:30', '10:00'], // 90 minutes
                                    ['10:00', '11:00'], // 60 minutes
                                    ['11:00', '12:30'], // 90 minutes
                                    ['12:30', '13:30'], // 60 minutes
                                    ['13:00', '14:30'], // 90 minutes
                                    ['14:30', '15:30'], // 60 minutes
                                    ['15:30', '17:00'], // 90 minutes
                                    ['17:00', '18:00']  // 60 minutes
                                ];
                                foreach ($times as $time) {
                                    echo "<tr>";
                                    echo "<td class='border-b border-gray-200 p-2 text-sm text-gray-700 bg-gray-50'>{$time[0]} - {$time[1]}</td>";
                                    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
                                        $schedule = array_filter($schedules, fn($s) => $s['day_of_week'] === $day && substr($s['start_time'], 0, 5) === $time[0] && substr($s['end_time'], 0, 5) === $time[1]);
                                        $schedule = reset($schedule) ?: null;
                                        echo "<td class='droppable border-b border-gray-200 p-2 text-sm text-gray-700 min-h-[60px] relative' data-time='{$time[0]}' data-end-time='{$time[1]}' data-day='{$day}'>";
                                        if ($schedule) {
                                            echo "<div class='schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative' data-id='{$schedule['schedule_id']}'>";
                                            echo "<strong class='text-gray-900'>{$schedule['course_code']} - {$schedule['course_name']}</strong><br>";
                                            echo "<span class='text-gray-600 text-xs'>{$schedule['faculty_name']}</span><br>";
                                            echo "<span class='text-gray-600 text-xs'>" . (isset($schedule['room_name']) ? $schedule['room_name'] : 'Online') . "</span><br>";
                                            echo "<span class='text-gray-600 text-xs'>{$schedule['section_name']} ({$schedule['schedule_type']})</span>";
                                            echo "<div class='mt-2'>";
                                            echo "<button class='remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded' onclick='removeSchedule(this)'>Remove</button>";
                                            echo "<button class='edit-btn ml-2 text-blue-500 text-xs p-1 hover:bg-blue-100 rounded' onclick='editScheduleInline(this)'>Edit</button>";
                                            echo "</div>";
                                            echo "</div>";
                                        } else {
                                            echo "<button class='add-btn text-green-500 text-xs p-1 hover:bg-green-100 rounded' onclick='addScheduleInline(this)'>Add</button>";
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
                    <?php
                    $unassignedWarning = false;
                    if (isset($schedules) && !empty($schedules)) {
                        $unassignedWarning = true; // Replace with actual check from generateSchedules
                    }
                    ?>
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
                        <?php if ($unassignedWarning): ?>
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md mb-6 shadow-md">
                                <p class="font-medium">Warning: Some subjects could not be scheduled. Please click "Generate Schedules" again to attempt filling the remaining slots.</p>
                                <button type="submit" class="mt-2 bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-200">
                                    Generate Schedules Again
                                </button>
                            </div>
                        <?php endif; ?>
                        <div>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                Generate Schedules
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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
                                $timeSlots = [
                                    ['07:30', '08:30'], // 60 minutes
                                    ['08:30', '10:00'], // 90 minutes
                                    ['10:00', '11:00'], // 60 minutes
                                    ['11:00', '12:30'], // 90 minutes
                                    ['12:30', '13:30'], // 60 minutes
                                    ['13:00', '14:30'], // 90 minutes
                                    ['14:30', '15:30'], // 60 minutes
                                    ['15:30', '17:00'], // 90 minutes
                                    ['17:00', '18:00']  // 60 minutes
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

                                    // Group by start time for rendering
                                    if (!isset($scheduleGrid[$day][$startTime])) {
                                        $scheduleGrid[$day][$startTime] = [];
                                    }
                                    $scheduleGrid[$day][$startTime][] = $schedule;
                                }
                                ?>

                                <?php foreach ($timeSlots as $time): ?>
                                    <?php
                                    $duration = strtotime($time[1]) - strtotime($time[0]);
                                    $rowSpan = $duration / 3600; // Convert seconds to hours
                                    ?>
                                    <div class="grid grid-cols-7 min-h-[<?php echo $rowSpan * 80; ?>px] hover:bg-gray-50 transition-colors duration-200" style="grid-row: span <?php echo $rowSpan; ?>;">
                                        <div class="px-4 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 flex items-center" rowspan="<?php echo $rowSpan; ?>">
                                            <span class="text-lg"><?php echo date('g:i A', strtotime($time[0])) . ' - ' . date('g:i A', strtotime($time[1])); ?></span>
                                        </div>
                                        <?php foreach ($days as $day): ?>
                                            <div class="px-2 py-2 border-r border-gray-200 last:border-r-0 min-h-[<?php echo $rowSpan * 80; ?>px] relative" data-day="<?php echo $day; ?>" data-time="<?php echo $time[0]; ?>" data-end-time="<?php echo $time[1]; ?>">
                                                <?php
                                                $schedulesForSlot = isset($scheduleGrid[$day][$time[0]]) ? $scheduleGrid[$day][$time[0]] : [];
                                                foreach ($schedulesForSlot as $schedule) {
                                                    $scheduleStart = substr($schedule['start_time'], 0, 5);
                                                    $scheduleEnd = substr($schedule['end_time'], 0, 5);
                                                    if ($scheduleStart === $time[0]) { // Match by start time only
                                                        $colors = [
                                                            'bg-blue-100 border-blue-300 text-blue-800',
                                                            'bg-green-100 border-green-300 text-green-800',
                                                            'bg-purple-100 border-purple-300 text-purple-800',
                                                            'bg-orange-100 border-orange-300 text-orange-800',
                                                            'bg-pink-100 border-pink-300 text-pink-800'
                                                        ];
                                                        $colorClass = $colors[array_rand($colors)];
                                                ?>
                                                        <div class="<?php echo $colorClass; ?> p-2 rounded-lg border-l-4 mb-1" data-year-level="<?php echo htmlspecialchars($schedule['year_level']); ?>" data-section-name="<?php echo htmlspecialchars($schedule['section_name']); ?>">
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
                                <span class="text-gray-600">Different colors for visual distinction</span>
                            </div>
                        </div>
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
    const departmentId = <?php echo json_encode($departmentId); ?>;
    const times = [
        ['07:30', '08:30'], // 60 minutes
        ['08:30', '10:00'], // 90 minutes
        ['10:00', '11:00'], // 60 minutes
        ['11:00', '12:30'], // 90 minutes
        ['12:30', '13:30'], // 60 minutes
        ['13:00', '14:30'], // 90 minutes
        ['14:30', '15:30'], // 60 minutes
        ['15:30', '17:00'], // 90 minutes
        ['17:00', '18:00'] // 60 minutes
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
            const schedule = scheduleData.find(s => s.day_of_week === cell.dataset.day && s.start_time.substring(0, 5) === cell.dataset.time && s.end_time.substring(0, 5) === cell.dataset.endTime);
            if (schedule) {
                cell.innerHTML = `<div class="schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative" data-id="${schedule.schedule_id ?? ''}"><strong class="text-gray-900">${schedule.course_code} - ${schedule.course_name}</strong><br><span class="text-gray-600 text-xs">${schedule.faculty_name}</span><br><span class="text-gray-600 text-xs">${schedule.room_name ?? 'Online'}</span><br><span class="text-gray-600 text-xs">${schedule.section_name} (${schedule.schedule_type})</span><div class="mt-2"><button class="remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded" onclick="removeSchedule(this)">Remove</button><button class="edit-btn ml-2 text-blue-500 text-xs p-1 hover:bg-blue-100 rounded" onclick="editScheduleInline(this)">Edit</button></div></div>`;
            } else {
                cell.innerHTML = `<button class="add-btn text-green-500 text-xs p-1 hover:bg-green-100 rounded" onclick="addScheduleInline(this)">Add</button>`;
            }
        });
    }

    function filterSchedules() {
        const yearLevel = document.getElementById('filterYearLevel').value;
        const section = document.getElementById('filterSection').value;
        const rows = document.querySelectorAll('#timetableGrid > div');
        rows.forEach(row => {
            const cells = row.querySelectorAll('div:nth-child(n+2)');
            cells.forEach(cell => {
                const scheduleItems = cell.querySelectorAll('[data-year-level]');
                let show = !scheduleItems.length; // Show empty cells by default
                scheduleItems.forEach(item => {
                    const itemYearLevel = item.getAttribute('data-year-level');
                    const itemSectionName = item.getAttribute('data-section-name');
                    const matchesYear = !yearLevel || itemYearLevel === yearLevel;
                    const matchesSection = !section || itemSectionName === section;
                    if (matchesYear && matchesSection) {
                        show = true;
                    }
                });
                cell.style.display = show ? 'block' : 'none';
            });
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
                        const item = document.querySelector(`[data-id="${scheduleId}"]`);
                        if (item) item.closest('td').innerHTML = '<button class="add-btn text-green-500 text-xs p-1 hover:bg-green-100 rounded" onclick="addScheduleInline(this)">Add</button>';
                        scheduleData = scheduleData.filter(s => s.schedule_id !== scheduleId);
                        document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
                        alert('Schedule deleted successfully.');
                    } else {
                        alert('Failed to delete schedule.');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }

    function editScheduleInline(button) {
        const item = button.parentElement.parentElement;
        const schedule = scheduleData.find(s => s.schedule_id === item.dataset.id) || {};
        const cell = item.closest('td');
        item.remove();

        cell.innerHTML = `
            <form class="edit-form p-2 bg-white rounded-md shadow-sm" onsubmit="saveEdit(event, this, '${schedule.schedule_id}')">
                <input type="hidden" name="schedule_id" value="${schedule.schedule_id ?? ''}">
                <input type="hidden" name="day_of_week" value="${cell.dataset.day}">
                <input type="hidden" name="start_time" value="${cell.dataset.time}">
                <input type="hidden" name="end_time" value="${cell.dataset.endTime}">
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Course</label>
                    <input type="text" name="course_code" value="${schedule.course_code ?? ''}" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs" required>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Faculty</label>
                    <input type="text" name="faculty_name" value="${schedule.faculty_name ?? ''}" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs" required>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Room</label>
                    <input type="text" name="room_name" value="${schedule.room_name ?? 'Online'}" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs">
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Section</label>
                    <input type="text" name="section_name" value="${schedule.section_name ?? ''}" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" class="bg-gray-300 text-gray-800 px-2 py-1 rounded-md text-xs hover:bg-gray-400" onclick="cancelEdit(this)">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded-md text-xs hover:bg-blue-700">Save</button>
                </div>
            </form>`;
    }

    function saveEdit(event, form, scheduleId) {
        event.preventDefault();
        const formData = new FormData(form);
        fetch('/chair/updateSchedule', {
                method: 'POST',
                body: new URLSearchParams({
                    schedule_id: scheduleId,
                    data: JSON.stringify(Object.fromEntries(formData))
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule updated successfully.');
                    const cell = form.closest('td');
                    cell.innerHTML = `<div class="schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative" data-id="${scheduleId}"><strong class="text-gray-900">${formData.get('course_code')}</strong><br><span class="text-gray-600 text-xs">${formData.get('faculty_name')}</span><br><span class="text-gray-600 text-xs">${formData.get('room_name')}</span><br><span class="text-gray-600 text-xs">${formData.get('section_name')}</span><div class="mt-2"><button class="remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded" onclick="removeSchedule(this)">Remove</button><button class="edit-btn ml-2 text-blue-500 text-xs p-1 hover:bg-blue-100 rounded" onclick="editScheduleInline(this)">Edit</button></div></div>`;
                    scheduleData = scheduleData.map(s => s.schedule_id === scheduleId ? {
                        ...s,
                        ...Object.fromEntries(formData)
                    } : s);
                    document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
                } else {
                    alert('Failed to update schedule.');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function cancelEdit(button) {
        const form = button.closest('.edit-form');
        const cell = form.closest('td');
        renderSchedules();
    }

    function addScheduleInline(button) {
        const cell = button.closest('td');
        cell.innerHTML = `
            <form class="add-form p-2 bg-white rounded-md shadow-sm" onsubmit="saveAdd(event, this)">
                <input type="hidden" name="day_of_week" value="${cell.dataset.day}">
                <input type="hidden" name="start_time" value="${cell.dataset.time}">
                <input type="hidden" name="end_time" value="${cell.dataset.endTime}">
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Course</label>
                    <input type="text" name="course_code" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs" required>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Faculty</label>
                    <input type="text" name="faculty_name" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs" required>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Room</label>
                    <input type="text" name="room_name" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs">
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700">Section</label>
                    <input type="text" name="section_name" class="w-full px-2 py-1 border border-gray-200 rounded-md text-xs" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" class="bg-gray-300 text-gray-800 px-2 py-1 rounded-md text-xs hover:bg-gray-400" onclick="cancelAdd(this)">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded-md text-xs hover:bg-blue-700">Add</button>
                </div>
            </form>`;
    }

    function saveAdd(event, form) {
        event.preventDefault();
        const formData = new FormData(form);
        fetch('/chair/addSchedule', {
                method: 'POST',
                body: new URLSearchParams({
                    data: JSON.stringify(Object.fromEntries(formData))
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule added successfully.');
                    const cell = form.closest('td');
                    const newSchedule = {
                        schedule_id: data.schedule_id,
                        ...Object.fromEntries(formData)
                    };
                    scheduleData.push(newSchedule);
                    cell.innerHTML = `<div class="schedule-item bg-blue-100 p-2 rounded-md shadow-sm relative" data-id="${newSchedule.schedule_id}"><strong class="text-gray-900">${formData.get('course_code')}</strong><br><span class="text-gray-600 text-xs">${formData.get('faculty_name')}</span><br><span class="text-gray-600 text-xs">${formData.get('room_name') ?? 'Online'}</span><br><span class="text-gray-600 text-xs">${formData.get('section_name')}</span><div class="mt-2"><button class="remove-btn text-red-500 text-xs p-1 hover:bg-red-100 rounded" onclick="removeSchedule(this)">Remove</button><button class="edit-btn ml-2 text-blue-500 text-xs p-1 hover:bg-blue-100 rounded" onclick="editScheduleInline(this)">Edit</button></div></div>`;
                    document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
                } else {
                    alert('Failed to add schedule.');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function cancelAdd(button) {
        const form = button.closest('.add-form');
        const cell = form.closest('td');
        renderSchedules();
    }

    function removeSchedule(button) {
        const item = button.parentElement.parentElement;
        const scheduleId = item.dataset.id;
        deleteSchedule(scheduleId);
    }

    if (document.getElementById('scheduleBody').children.length === 0) {
        populateSchedule();
    }
    if (document.getElementById('timetableGrid')) {
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