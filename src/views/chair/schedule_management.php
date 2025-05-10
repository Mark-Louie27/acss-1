<?php
ob_start();

?>

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
        <i class="far fa-calendar-alt mr-2 text-yellow-500"></i>
        <span>Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?></span>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                <a href="?tab=manual" class="tab-button <?php echo ($activeTab === 'manual' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'); ?> py-3 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-calendar-plus mr-2"></i>Manual Creation
                </a>
                <a href="?tab=generate" class="tab-button <?php echo ($activeTab === 'generate' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'); ?> py-3 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-magic mr-2"></i>Generate Schedules
                </a>
                <a href="?tab=export" class="tab-button <?php echo ($activeTab === 'export' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'); ?> py-3 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-file-export mr-2"></i>Export Template
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

                <!-- Selectors for Curriculum, Faculty, Course, Room, and Section -->
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="w-full md:w-1/4">
                        <label for="curriculum_id" class="block text-sm font-medium text-gray-700 mb-1">Curriculum</label>
                        <select id="curriculum_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500" onchange="updateCoursesAndSections()">
                            <option value="">Select Curriculum</option>
                            <?php foreach ($curricula as $curriculum): ?>
                                <option value="<?php echo htmlspecialchars($curriculum['curriculum_id']); ?>"
                                    <?php echo $curriculum['curriculum_id'] == ($selectedCurriculumId ?? '') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($curriculum['curriculum_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="faculty_id" class="block text-sm font-medium text-gray-700 mb-1">Faculty</label>
                        <select id="faculty_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Select Faculty</option>
                            <?php foreach ($faculty as $fac): ?>
                                <option value="<?php echo htmlspecialchars($fac['faculty_id']); ?>">
                                    <?php echo htmlspecialchars($fac['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select id="course_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Select Course</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <select id="room_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Select Room</option>
                            <?php foreach ($classrooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <select id="section_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Select Section</option>
                        </select>
                    </div>
                </div>

                <!-- Draggable Box -->
                <div id="draggableBox" class="draggable-box bg-gray-100 border border-gray-200 rounded-md p-4 text-center mb-4" draggable="false">
                    <p>Select curriculum, faculty, course, room, and section to drag</p>
                </div>

                <!-- Schedule Table -->
                <div class="overflow-x-auto">
                    <table class="schedule-table w-full border-collapse bg-white" id="scheduleTable">
                        <thead>
                            <tr>
                                <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700">Time</th>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
                                    <th class="border border-gray-200 p-2 bg-gray-100 text-sm font-medium text-gray-700"><?php echo $day; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody id="scheduleBody"></tbody>
                    </table>
                </div>

                <!-- Hidden Form for Submission -->
                <form method="POST" id="scheduleForm" class="mt-6">
                    <input type="hidden" name="tab" value="manual">
                    <input type="hidden" name="schedules" id="schedulesInput">
                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">Save Schedule</button>
                    <button type="button" class="print-btn print-hide bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 ml-2" onclick="window.print()">Print Schedule</button>
                    <p class="text-sm text-gray-600 mt-2">A plain Excel file with resources will also be exported for drag-and-drop editing.</p>
                </form>
            </div>
        </div>

        <!-- Generate Schedules Tab -->
        <div class="<?php echo ($activeTab === 'generate' ? '' : 'hidden'); ?>">
            <form method="POST" class="bg-white p-6 rounded-lg shadow-md">
                <input type="hidden" name="tab" value="generate">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="generate_curriculum_id" class="block text-sm font-medium text-gray-700 mb-1">Curriculum</label>
                        <select name="curriculum_id" id="generate_curriculum_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500" required onchange="updateGenerateSections()">
                            <option value="">Select Curriculum</option>
                            <?php foreach ($curricula as $curriculum): ?>
                                <option value="<?php echo htmlspecialchars($curriculum['curriculum_id']); ?>">
                                    <?php echo htmlspecialchars($curriculum['curriculum_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Semester: <?php echo htmlspecialchars($currentSemester['semester_name'] ?? 'Not Set'); ?></label>
                        <input type="hidden" name="semester_id" value="<?php echo htmlspecialchars($currentSemester['semester_id'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="year_levels" class="block text-sm font-medium text-gray-700 mb-1">Year Levels</label>
                        <select name="year_levels[]" id="year_levels" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500" multiple>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div>
                        <label for="sections" class="block text-sm font-medium text-gray-700 mb-1">Sections</label>
                        <select name="sections[]" id="sections" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500" multiple>
                            <option value="">Select Curriculum First</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">Generate Schedules</button>
                </div>
            </form>
        </div>

        <!-- Export Template Tab -->
        <div class="<?php echo ($activeTab === 'export' ? '' : 'hidden'); ?>">
            <form method="POST" class="bg-white p-6 rounded-lg shadow-md">
                <input type="hidden" name="tab" value="export">
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Export a plain Excel file containing all available resources (curricula, courses, faculty, rooms, sections) for manual editing.</p>
                </div>
                <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">Export Template</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Pass departmentId from PHP to JavaScript
    const departmentId = <?php echo json_encode($departmentId); ?>;
    console.log('Department ID:', departmentId);

    // Time slots
    const times = [{
            start: '07:30',
            end: '08:30'
        },
        {
            start: '08:30',
            end: '09:30'
        },
        {
            start: '09:30',
            end: '10:30'
        },
        {
            start: '10:30',
            end: '11:30'
        },
        {
            start: '11:30',
            end: '12:30'
        },
        {
            start: '12:30',
            end: '13:30'
        },
        {
            start: '13:30',
            end: '14:30'
        },
        {
            start: '14:30',
            end: '15:30'
        },
        {
            start: '15:30',
            end: '16:30'
        },
        {
            start: '16:30',
            end: '17:30'
        },
        {
            start: '17:30',
            end: '18:00'
        }
    ];
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // Populate schedule grid
    const tbody = document.getElementById('scheduleBody');
    times.forEach(time => {
        const row = document.createElement('tr');
        const timeCell = document.createElement('td');
        timeCell.textContent = `${time.start} - ${time.end}`;
        timeCell.classList.add('border', 'border-gray-200', 'p-2', 'text-sm', 'text-gray-700');
        row.appendChild(timeCell);
        days.forEach(day => {
            const cell = document.createElement('td');
            cell.dataset.time = time.start;
            cell.dataset.endTime = time.end;
            cell.dataset.day = day;
            cell.classList.add('droppable', 'border', 'border-gray-200', 'p-2', 'text-sm', 'text-gray-700', 'min-h-[60px]');
            row.appendChild(cell);
        });
        tbody.appendChild(row);
    });

    // Update courses and sections based on curriculum
    function updateCoursesAndSections() {
        const curriculumId = document.getElementById('curriculum_id').value;
        const courseSelect = document.getElementById('course_id');
        const sectionSelect = document.getElementById('section_id');

        // Reset dropdowns
        courseSelect.innerHTML = '<option value="">Select Course</option>';
        sectionSelect.innerHTML = '<option value="">Select Section</option>';

        if (!curriculumId) {
            updateDraggableBox();
            return;
        }

        // Log fetch attempt
        console.log('Fetching courses for curriculum_id:', curriculumId);

        // Fetch courses
        fetch(`/api/load_data?type=courses&curriculum_id=${curriculumId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => ({
                    text,
                    response
                }));
            })
            .then(({
                text,
                response
            }) => {
                console.log('Courses raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Courses parsed data:', data);

                    // Clear existing options first
                    courseSelect.innerHTML = '';

                    if (data.success && Array.isArray(data.data)) {
                        if (data.data.length === 0) {
                            courseSelect.innerHTML = '<option value="">No courses available for this curriculum</option>';
                            alert('No courses found for the selected curriculum.');
                        } else {
                            data.data.forEach(course => {
                                const option = new Option(
                                    `${course.course_code} - ${course.course_name}`,
                                    course.course_id
                                );
                                option.dataset.curriculumId = curriculumId;
                                option.dataset.yearLevel = course.year_level;
                                option.dataset.semester = course.semester;
                                courseSelect.appendChild(option);
                            });
                        }
                    } else {
                        console.error('Courses response error:', data.message || 'Invalid response structure');
                        courseSelect.innerHTML = '<option value="">Error: Failed to load courses</option>';
                        alert('Failed to load courses: ' + (data.message || 'Invalid response from server'));
                    }
                } catch (e) {
                    console.error('Courses JSON parse error:', e.message, 'Raw response:', text);
                    courseSelect.innerHTML = '<option value="">Error: Invalid server response</option>';
                    alert('Error parsing courses response: ' + e.message);
                }
            })
            .catch(error => {
                console.error('Courses fetch error:', error.message);
                courseSelect.innerHTML = '<option value="">Error: Unable to fetch courses</option>';
                alert('Error fetching courses: ' + error.message);
            });

        // Log fetch attempt
        console.log('Fetching sections for curriculum_id:', curriculumId, 'department_id:', departmentId);

        // Fetch sections
        fetch(`/api/load_data?type=sections&curriculum_id=${curriculumId}&department_id=${departmentId}`)
            .then(response => {
                console.log('Sections fetch status:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => ({
                    text,
                    response
                }));
            })
            .then(({
                text,
                response
            }) => {
                console.log('Sections raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Sections parsed data:', data);
                    if (data.success && Array.isArray(data.data)) {
                        if (data.data.length === 0) {
                            sectionSelect.innerHTML = '<option value="">No sections available</option>';
                            console.warn('No sections returned for curriculum_id:', curriculumId, 'department_id:', departmentId);
                        } else {
                            data.data.forEach(section => {
                                const option = new Option(
                                    `${section.section_name} (${section.year_level})`,
                                    section.section_id
                                );
                                option.dataset.curriculumId = section.curriculum_id || curriculumId;
                                option.dataset.yearLevel = section.year_level;
                                sectionSelect.appendChild(option);
                            });
                        }
                    } else {
                        console.error('Sections response error:', data.message || 'Invalid response structure');
                        sectionSelect.innerHTML = '<option value="">No sections available</option>';
                        alert('Failed to load sections: ' + (data.message || 'Invalid response structure'));
                    }
                } catch (e) {
                    console.error('Sections JSON parse error:', e.message, 'Raw response:', text);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    alert('Error parsing sections response: ' + e.message);
                }
            })
            .catch(error => {
                console.error('Sections fetch error:', error.message);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                alert('Error fetching sections: ' + error.message);
            });

        updateDraggableBox();
    }

    // Update sections in the Generate tab
    function updateGenerateSections() {
        const curriculumId = document.getElementById('generate_curriculum_id').value;
        const sectionSelect = document.getElementById('sections');

        // Reset dropdown
        sectionSelect.innerHTML = '<option value="">Select Section</option>';

        if (!curriculumId) {
            return;
        }

        // Log fetch attempt
        console.log('Fetching generate sections for curriculum_id:', curriculumId, 'department_id:', departmentId);

        // Fetch sections
        fetch(`/api/load_data?type=sections&curriculum_id=${curriculumId}&department_id=${departmentId}`)
            .then(response => {
                console.log('Generate Sections fetch status:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => ({
                    text,
                    response
                }));
            })
            .then(({
                text,
                response
            }) => {
                console.log('Generate Sections raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Generate Sections parsed data:', data);
                    if (data.success && Array.isArray(data.data)) {
                        if (data.data.length === 0) {
                            sectionSelect.innerHTML = '<option value="">No sections available</option>';
                            console.warn('No sections returned for curriculum_id:', curriculumId, 'department_id:', departmentId);
                        } else {
                            data.data.forEach(section => {
                                const option = new Option(
                                    `${section.section_name} (${section.year_level})`,
                                    section.section_id
                                );
                                option.dataset.curriculumId = section.curriculum_id || curriculumId;
                                option.dataset.yearLevel = section.year_level;
                                sectionSelect.appendChild(option);
                            });
                        }
                    } else {
                        console.error('Generate Sections response error:', data.message || 'Invalid response structure');
                        sectionSelect.innerHTML = '<option value="">No sections available</option>';
                        alert('Failed to load sections: ' + (data.message || 'Invalid response structure'));
                    }
                } catch (e) {
                    console.error('Generate Sections JSON parse error:', e.message, 'Raw response:', text);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    alert('Error parsing sections response: ' + e.message);
                }
            })
            .catch(error => {
                console.error('Generate Sections fetch error:', error.message);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                alert('Error fetching sections: ' + error.message);
            });
    }

    // Update draggable box text
    const curriculumSelect = document.getElementById('curriculum_id');
    const facultySelect = document.getElementById('faculty_id');
    const courseSelect = document.getElementById('course_id');
    const roomSelect = document.getElementById('room_id');
    const sectionSelect = document.getElementById('section_id');
    const draggableBox = document.getElementById('draggableBox');

    function updateDraggableBox() {
        const curriculum = curriculumSelect.options[curriculumSelect.selectedIndex]?.text;
        const faculty = facultySelect.options[facultySelect.selectedIndex]?.text;
        const course = courseSelect.options[courseSelect.selectedIndex]?.text;
        const room = roomSelect.options[roomSelect.selectedIndex]?.text;
        const section = sectionSelect.options[sectionSelect.selectedIndex]?.text;

        if (curriculum && faculty && course && room && section && course !== 'Select Course' && section !== 'Select Section') {
            draggableBox.innerHTML = `<p><strong>${course}</strong><br>Faculty: ${faculty}<br>Room: ${room}<br>Section: ${section}</p>`;
            draggableBox.setAttribute('draggable', 'true');
        } else {
            draggableBox.innerHTML = `<p>Select curriculum, faculty, course, room, and section to drag</p>`;
            draggableBox.setAttribute('draggable', 'false');
        }
    }

    curriculumSelect.addEventListener('change', updateCoursesAndSections);
    facultySelect.addEventListener('change', updateDraggableBox);
    courseSelect.addEventListener('change', updateDraggableBox);
    roomSelect.addEventListener('change', updateDraggableBox);
    sectionSelect.addEventListener('change', updateDraggableBox);

    // Drag-and-drop logic
    const scheduleData = [];
    document.querySelectorAll('.droppable').forEach(cell => {
        cell.addEventListener('dragover', e => {
            e.preventDefault();
            cell.classList.add('bg-gray-100');
        });

        cell.addEventListener('dragleave', () => {
            cell.classList.remove('bg-gray-100');
        });

        cell.addEventListener('drop', e => {
            e.preventDefault();
            cell.classList.remove('bg-gray-100');

            const curriculumId = curriculumSelect.value;
            const facultyId = facultySelect.value;
            const courseId = courseSelect.value;
            const roomId = roomSelect.value;
            const sectionId = sectionSelect.value;

            if (curriculumId && facultyId && courseId && roomId && sectionId) {
                const courseOption = courseSelect.options[courseSelect.selectedIndex];
                const sectionOption = sectionSelect.options[sectionSelect.selectedIndex];
                const facultyName = facultySelect.options[facultySelect.selectedIndex].text;
                const roomName = roomSelect.options[roomSelect.selectedIndex].text;
                const sectionName = sectionOption.text;
                const day = cell.dataset.day;
                const startTime = cell.dataset.time;
                const endTime = cell.dataset.endTime;

                // Validate curriculum match
                if (courseOption.dataset.curriculumId !== curriculumId || sectionOption.dataset.curriculumId !== curriculumId) {
                    alert('Course and section must belong to the selected curriculum.');
                    return;
                }

                // Display in table
                cell.innerHTML = `<div class="schedule-item"><strong>${courseOption.text}</strong><br>${facultyName}<br>${roomName}<br>${sectionName}<button class="text-red-500 text-xs mt-1" onclick="this.parentElement.remove(); updateSchedules()">Remove</button></div>`;

                // Store schedule data
                scheduleData.push({
                    curriculum_id: curriculumId,
                    course_id: courseId,
                    faculty_id: facultyId,
                    room_id: roomId,
                    section_id: sectionId,
                    day_of_week: day,
                    start_time: startTime,
                    end_time: endTime
                });

                // Update hidden input
                document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
            } else {
                alert('Please select curriculum, faculty, course, room, and section.');
            }
        });
    });

    // Draggable box drag events
    draggableBox.addEventListener('dragstart', e => {
        if (curriculumSelect.value && facultySelect.value && courseSelect.value && roomSelect.value && sectionSelect.value) {
            e.dataTransfer.setData('text', draggableBox.innerHTML);
        } else {
            e.preventDefault();
        }
    });

    draggableBox.addEventListener('dragend', () => {
        draggableBox.style.opacity = 1;
    });

    // Update schedules when removing items
    function updateSchedules() {
        scheduleData.length = 0;
        document.querySelectorAll('.schedule-item').forEach(item => {
            const cell = item.parentElement;
            const curriculumId = curriculumSelect.value;
            const courseId = courseSelect.value;
            const facultyId = facultySelect.value;
            const roomId = roomSelect.value;
            const sectionId = sectionSelect.value;
            if (curriculumId && courseId && facultyId && roomId && sectionId) {
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
            }
        });
        document.getElementById('schedulesInput').value = JSON.stringify(scheduleData);
    }
</script>

<style>
    .draggable-box {
        width: 250px;
        min-height: 100px;
        border: 1px solid #ccc;
        padding: 10px;
        background-color: #f9f9f9;
        cursor: move;
        text-align: center;
    }

    .droppable {
        min-width: 120px;
        min-height: 60px;
        overflow: auto;
        font-size: 12px;
    }

    .schedule-item {
        position: relative;
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

        .droppable {
            font-size: 10px;
        }

        .print-hide {
            display: none;
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