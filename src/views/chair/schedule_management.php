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
            <form method="POST" class="bg-white p-6 rounded-lg shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select name="course_id" id="course_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="faculty_id" class="block text-sm font-medium text-gray-700 mb-1">Faculty</label>
                        <select name="faculty_id" id="faculty_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Faculty</option>
                            <?php foreach ($faculty as $fac): ?>
                                <option value="<?php echo htmlspecialchars($fac['faculty_id']); ?>">
                                    <?php echo htmlspecialchars($fac['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <select name="room_id" id="room_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Room</option>
                            <?php foreach ($classrooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <select name="section_id" id="section_id" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Section</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['section_id']); ?>">
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="day_of_week" class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                        <select name="day_of_week" id="day_of_week" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select a Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time (HH:MM)</label>
                        <select name="start_time" id="start_time" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select Start Time</option>
                            <?php $times = ['07:30', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
                            foreach ($times as $time): ?>
                                <option value="<?php echo htmlspecialchars($time); ?>"><?php echo htmlspecialchars($time); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time (HH:MM)</label>
                        <select name="end_time" id="end_time" class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" required>
                            <option value="">Select End Time</option>
                            <?php foreach ($times as $time): ?>
                                <option value="<?php echo htmlspecialchars($time); ?>"><?php echo htmlspecialchars($time); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-gold-500 text-white px-4 py-2 rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500">Create Schedule</button>
                    <p class="text-sm text-gray-600 mt-2">A plain Excel file with resources will also be exported for drag-and-drop editing.</p>
                </div>
            </form>
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
<?php $content = ob_get_clean();
require_once __DIR__ . '/layout.php'; ?>