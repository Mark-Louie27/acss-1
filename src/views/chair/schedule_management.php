<?php ob_start(); ?>
<div class="p-6 bg-gray-50 min-h-screen font-sans">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Schedule Management</h2>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
            <?php echo nl2br(htmlspecialchars($error)); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Display Current Semester -->
    <div class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow-sm flex items-center mb-6">
        <i class="far fa-calendar-alt mr-2 text-gold-500"></i>
        <span>Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?></span>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                <a href="?tab=manual" class="tab-button <?php echo ($activeTab === 'manual' ? 'border-gold-500 text-gold-600' : 'border-transparent text-gray-500 hover:text-gray-700'); ?> py-3 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-calendar-plus mr-2"></i>Manual Creation
                </a>
                <a href="?tab=generate" class="tab-button <?php echo ($activeTab === 'generate' ? 'border-gold-500 text-gold-600' : 'border-transparent text-gray-500 hover:text-gray-700'); ?> py-3 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-magic mr-2"></i>Generate Schedules
                </a>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Manual Creation Tab -->
        <div class="<?php echo ($activeTab === 'manual' ? '' : 'hidden'); ?>">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Faculty Scheduling Planner</h3>

                <!-- Selectors for Faculty and Subject -->
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="w-full md:w-1/3">
                        <label for="faculty" class="block text-sm font-medium text-gray-700 mb-1">Faculty</label>
                        <select id="faculty" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500">
                            <option value="">-- Select Faculty --</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/3">
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select id="subject" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500">
                            <option value="">-- Select Course --</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/3">
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <select id="room_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Room</option>
                            <?php foreach ($classrooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-1/3">
                        <label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <select id="section_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Section</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['section_id']); ?>">
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Box to display selected Faculty and Subject for drag-and-drop -->
                <div id="draggableBox" class="draggable-box bg-gray-100 border border-gray-200 rounded-md p-4 text-center mb-4" draggable="true">
                    <p>Drag me to the schedule!</p>
                </div>

                <!-- Schedule Table -->
                <div class="overflow-x-auto">
                    <table class="schedule-table w-full border-collapse bg-white" id="scheduleTable">
                        <thead>
                            <tr>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Time</th>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Monday</th>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Tuesday</th>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Wednesday</th>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Thursday</th>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Friday</th>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Saturday</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleBody"></tbody>
                    </table>
                </div>

                <!-- Hidden Form for Submission -->
                <form method="POST" id="scheduleForm" class="mt-6">
                    <input type="hidden" name="schedules" id="schedulesInput">
                    <button type="submit" class="bg-gold-500 text-white px-4 py-2 rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500">Save Schedule</button>
                    <button type="button" class="print-btn print-hide bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 ml-2" onclick="window.print()">Print Schedule</button>
                    <p class="text-sm text-gray-600 mt-2">A plain Excel file with resources will also be exported for drag-and-drop editing.</p>
                </form>
            </div>
        </div>

        <!-- Generate Schedules Tab -->
        <div class="<?php echo ($activeTab === 'generate' ? '' : 'hidden'); ?>">
            <form method="POST" class="bg-white p-6 rounded-lg shadow-md">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?></label>
                        <input type="hidden" name="semester_id" value="<?php echo htmlspecialchars($currentSemester['semester_id'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="year_levels" class="block text-sm font-medium text-gray-700 mb-1">Year Levels</label>
                        <select name="year_levels[]" id="year_levels" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" multiple>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                    <div>
                        <label for="sections" class="block text-sm font-medium text-gray-700 mb-1">Sections</label>
                        <select name="sections[]" id="sections" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" multiple>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['section_id']); ?>">
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-gold-500 text-white px-4 py-2 rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500">Generate Schedules</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Data for time slots and days
    const times = [{
            start: '7:30 AM',
            end: '8:30 AM'
        },
        {
            start: '8:30 AM',
            end: '9:30 AM'
        },
        {
            start: '9:30 AM',
            end: '10:30 AM'
        },
        {
            start: '10:30 AM',
            end: '11:30 AM'
        },
        {
            start: '11:30 AM',
            end: '12:30 PM'
        },
        {
            start: '12:30 PM',
            end: '1:30 PM'
        },
        {
            start: '1:30 PM',
            end: '2:30 PM'
        },
        {
            start: '2:30 PM',
            end: '3:30 PM'
        },
        {
            start: '3:30 PM',
            end: '4:30 PM'
        },
        {
            start: '4:30 PM',
            end: '5:30 PM'
        },
        {
            start: '5:30 PM',
            end: '6:00 PM'
        }
    ];
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // Populate schedule grid dynamically
    const tbody = document.getElementById('scheduleBody');
    times.forEach(time => {
        const row = document.createElement('tr');
        const timeCell = document.createElement('td');
        timeCell.textContent = `${time.start} to ${time.end}`;
        timeCell.classList.add('border', 'border-gray-200', 'p-2', 'text-sm', 'text-gray-700');
        row.appendChild(timeCell);
        days.forEach(day => {
            const cell = document.createElement('td');
            cell.dataset.time = time.start;
            cell.dataset.endTime = time.end;
            cell.dataset.day = day;
            cell.classList.add('droppable', 'border', 'border-gray-200', 'p-2', 'text-sm', 'text-gray-700');
            row.appendChild(cell);
        });
        tbody.appendChild(row);
    });

    // Fetch Faculty and Course Data
    fetch('/api/load_data?type=faculty')
        .then(response => response.json())
        .then(data => {
            const facultySelect = document.getElementById('faculty');
            data.forEach(faculty => {
                let option = document.createElement('option');
                option.value = faculty.id;
                option.textContent = faculty.name;
                facultySelect.appendChild(option);
            });
        });

    fetch('/api/load_data?type=course')
        .then(response => response.json())
        .then(data => {
            const subjectSelect = document.getElementById('subject');
            data.forEach(course => {
                let option = document.createElement('option');
                option.value = course.id;
                option.textContent = `${course.code} - ${course.name}`;
                subjectSelect.appendChild(option);
            });
        });

    // Update the draggable box text based on selection
    const facultySelect = document.getElementById('faculty');
    const subjectSelect = document.getElementById('subject');
    const draggableBox = document.getElementById('draggableBox');

    function updateDraggableBox() {
        const faculty = facultySelect.options[facultySelect.selectedIndex]?.text;
        const subject = subjectSelect.options[subjectSelect.selectedIndex]?.text;

        if (faculty && subject) {
            draggableBox.innerHTML = `<p><strong>${subject}</strong><br>Faculty: ${faculty}</p>`;
        }
    }

    facultySelect.addEventListener('change', updateDraggableBox);
    subjectSelect.addEventListener('change', updateDraggableBox);

    // Drag-and-Drop Logic for the Schedule
    const scheduleData = [];
    document.querySelectorAll('.droppable').forEach(cell => {
        cell.addEventListener('dragover', function(e) {
            e.preventDefault(); // Allow drop
            cell.classList.add('over');
        });

        cell.addEventListener('dragleave', function() {
            cell.classList.remove('over');
        });

        cell.addEventListener('drop', function(e) {
            e.preventDefault();
            const facultyId = facultySelect.value;
            const courseId = subjectSelect.value;
            const roomId = document.getElementById('room_id').value;
            const sectionId = document.getElementById('section_id').value;

            if (facultyId && courseId && roomId && sectionId) {
                const courseName = subjectSelect.options[subjectSelect.selectedIndex].text;
                const facultyName = facultySelect.options[facultySelect.selectedIndex].text;
                const day = cell.dataset.day;
                const startTime = cell.dataset.time;
                const endTime = cell.dataset.endTime;

                // Display in the table
                cell.innerHTML = `<strong>${courseName}</strong><br>${facultyName}`;
                cell.classList.remove('over');

                // Store the schedule data
                scheduleData.push({
                    course_id: courseId,
                    faculty_id: facultyId,
                    room_id: roomId,
                    section_id: sectionId,
                    day_of_week: day,
                    start_time: startTime.replace(' AM', '').replace(' PM', ''),
                    end_time: endTime.replace(' AM', '').replace(' PM', '')
                });

                // Update the hidden input with the schedule data
                document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
            } else {
                alert('Please select faculty, course, room, and section');
            }
        });
    });

    // Draggable text functionality
    draggableBox.addEventListener('dragstart', function(e) {
        const faculty = facultySelect.value;
        const subject = subjectSelect.value;

        if (faculty && subject) {
            const subjectName = subjectSelect.options[subjectSelect.selectedIndex].text;
            const facultyName = facultySelect.options[facultySelect.selectedIndex].text;
            e.dataTransfer.setData('text', `${subjectName} with ${facultyName}`);
        }
    });

    draggableBox.addEventListener('dragend', function() {
        draggableBox.style.opacity = 1; // Reset opacity after dragging
    });
</script>

<style>
    .draggable-box {
        width: 200px;
        height: 100px;
        border: 1px solid #ccc;
        padding: 10px;
        background-color: #f9f9f9;
        cursor: move;
        text-align: center;
    }

    .over {
        background-color: #f0f0f0;
    }

    .droppable {
        width: 120px;
        height: 60px;
        overflow: hidden;
        font-size: 12px;
    }

    /* Styles for Print */
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
            height: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .schedule-table th,
        .schedule-table td {
            font-size: 10px;
            padding: 5px;
            width: auto;
            height: auto;
        }

        .droppable {
            font-size: 16px;
        }

        .print-hide {
            display: none;
        }
    }
</style>

<?php $content = ob_get_clean();
require_once __DIR__ . '/layout.php'; 
?>