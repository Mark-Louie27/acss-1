<?php
// views/admin/classroom.php
ob_start();
?>

<style>
    :root {
        --gold: #D4AF37;
        --white: #FFFFFF;
        --gray-dark: #4B5563;
        --gray-light: #E5E7EB;
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .slide-in-left {
        animation: slideInLeft 0.5s ease-in;
    }

    @keyframes slideInLeft {
        from {
            transform: translateX(-20px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .btn-gold {
        background-color: var(--gold);
        color: var(--white);
    }

    .btn-gold:hover {
        background-color: #b8972e;
    }

    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .modal {
        transition: opacity 0.3s ease;
    }

    .modal.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .modal-content {
        transition: transform 0.3s ease;
    }

    /* Schedule Modal Styles */
    .schedule-day {
        margin-bottom: 1.5rem;
    }

    .schedule-day-header {
        background-color: var(--gray-dark);
        color: var(--white);
        padding: 0.75rem 1rem;
        border-radius: 0.5rem 0.5rem 0 0;
        font-weight: 600;
    }

    .schedule-session {
        background-color: var(--white);
        border: 1px solid var(--gray-light);
        border-top: none;
        padding: 1rem;
    }

    .schedule-session:last-child {
        border-radius: 0 0 0.5rem 0.5rem;
    }

    .schedule-time {
        font-weight: 600;
        color: var(--gray-dark);
        margin-bottom: 0.5rem;
    }

    .schedule-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .schedule-item {
        margin-bottom: 0.5rem;
    }

    .schedule-label {
        font-weight: 600;
        color: var(--gray-dark);
        display: inline-block;
        min-width: 80px;
    }

    .no-schedule {
        text-align: center;
        padding: 2rem;
        color: var(--gray-dark);
        background-color: var(--gray-light);
        border-radius: 0.5rem;
    }

    .print-only {
        display: none;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .schedule-modal-content,
        .schedule-modal-content * {
            visibility: visible;
        }

        .schedule-modal-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 20px;
            box-shadow: none;
        }

        .no-print {
            display: none !important;
        }

        .print-only {
            display: block;
        }

        .schedule-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--gray-dark);
            padding-bottom: 1rem;
        }
    }

    .tooltip {
        display: none;
    }

    .group:hover .tooltip {
        display: block;
    }
</style>

<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Toast Container -->
        <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 to-yellow-400 slide-in-left">
                        Classroom Management
                    </h1>
                    <p class="mt-2 text-gray-600 slide-in-right">View and manage classrooms across all colleges and departments</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="college_id" class="block text-sm font-medium text-gray-700 mb-2">College</label>
                        <select name="college_id" id="college_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Colleges</option>
                            <?php
                            $currentCollege = isset($_GET['college_id']) ? (int)$_GET['college_id'] : null;
                            foreach ($colleges as $college) {
                                $selected = $currentCollege == $college['college_id'] ? 'selected' : '';
                                echo "<option value=\"{$college['college_id']}\" $selected>" . htmlspecialchars($college['college_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select name="department_id" id="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Departments</option>
                            <?php
                            $currentDept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
                            foreach ($departments as $dept) {
                                $selected = $currentDept == $dept['department_id'] ? 'selected' : '';
                                echo "<option value=\"{$dept['department_id']}\" data-college=\"{$dept['college_id']}\" $selected>" . htmlspecialchars($dept['department_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="availability" class="block text-sm font-medium text-gray-700 mb-2">Availability</label>
                        <select name="availability" id="availability" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Statuses</option>
                            <option value="available" <?php echo isset($_GET['availability']) && $_GET['availability'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo isset($_GET['availability']) && $_GET['availability'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                            <option value="under_maintenance" <?php echo isset($_GET['availability']) && $_GET['availability'] === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="btn-gold px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            Filter
                        </button>
                        <button type="button" id="clearFilters" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classrooms Table -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Classroom Directory</h2>
                <span class="text-sm font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                    <?php echo count($classrooms); ?> Classrooms
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Building</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shared</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">College</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($classrooms as $classroom): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($classroom['room_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($classroom['building']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $classroom['capacity']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars(ucfirst($classroom['room_type'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $classroom['shared'] ? '<span class="text-green-600"><i class="fas fa-check"></i> Yes</span>' : '<span class="text-red-600"><i class="fas fa-times"></i> No</span>'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $classroom['availability'] === 'available' ? 'bg-green-100 text-green-800' : ($classroom['availability'] === 'unavailable' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $classroom['availability']))); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($classroom['department_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($classroom['college_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button class="view-schedule-btn bg-blue-500 text-white px-3 py-1 rounded shadow-md hover:shadow-lg transition-all duration-200 group relative"
                                        data-room-id="<?php echo $classroom['room_id']; ?>"
                                        data-room-name="<?php echo htmlspecialchars($classroom['room_name']); ?>"
                                        data-building="<?php echo htmlspecialchars($classroom['building']); ?>"
                                        data-department="<?php echo htmlspecialchars($classroom['department_name'] ?? 'N/A'); ?>"
                                        data-college="<?php echo htmlspecialchars($classroom['college_name'] ?? 'N/A'); ?>"
                                        title="View Schedule">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span class="tooltip absolute bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">View Schedule</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($classrooms)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-door-open text-4xl mb-4 text-gray-300"></i>
                    <p class="text-lg">No classrooms found.</p>
                    <p class="text-sm mt-1">Try adjusting your filters to see more results.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div id="scheduleModal" class="modal fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl mx-4 max-h-[90vh] overflow-y-auto modal-content scale-95 schedule-modal-content">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 rounded-t-xl sticky top-0 z-10">
            <h3 class="text-xl font-bold text-gray-900" id="scheduleModalTitle">Classroom Schedule</h3>
            <div class="flex items-center space-x-2">
                <button id="printScheduleBtn" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-all duration-200 flex items-center space-x-2 no-print">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button id="closeScheduleModalBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-200 hover:bg-gray-300 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="print-only schedule-header mb-6">
                <h2 class="text-2xl font-bold text-gray-900" id="printTitle"></h2>
                <p class="text-lg text-gray-700" id="printSubtitle"></p>
                <p class="text-sm text-gray-600" id="printDate"></p>
            </div>
            <div id="scheduleContent">
                <!-- Schedule content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // College-Department Filter Logic
        const collegeSelect = document.getElementById('college_id');
        const departmentSelect = document.getElementById('department_id');
        const clearFiltersBtn = document.getElementById('clearFilters');

        // Filter departments based on selected college
        collegeSelect.addEventListener('change', function() {
            const collegeId = this.value;
            const currentDepartment = departmentSelect.value;

            // Reset department options
            departmentSelect.innerHTML = '<option value="">All Departments</option>';

            if (collegeId) {
                // Filter departments to show only those from selected college
                const departmentOptions = Array.from(departmentSelect.options).filter(option => {
                    return option.value === '' || option.getAttribute('data-college') === collegeId;
                });

                departmentOptions.forEach(option => {
                    departmentSelect.appendChild(option);
                });
            } else {
                // Show all departments
                const allDepartments = <?php echo json_encode($departments); ?>;
                allDepartments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.department_id;
                    option.textContent = dept.department_name;
                    option.setAttribute('data-college', dept.college_id);
                    departmentSelect.appendChild(option);
                });
            }

            // Preserve current selection if it belongs to the selected college
            if (currentDepartment) {
                const currentOption = departmentSelect.querySelector(`option[value="${currentDepartment}"]`);
                if (currentOption && (!collegeId || currentOption.getAttribute('data-college') === collegeId)) {
                    departmentSelect.value = currentDepartment;
                }
            }
        });

        // Clear filters
        clearFiltersBtn.addEventListener('click', () => {
            collegeSelect.value = '';
            departmentSelect.value = '';
            document.getElementById('availability').value = '';
            window.location.href = window.location.pathname;
        });

        // Schedule Modal Functions
        const scheduleModal = document.getElementById('scheduleModal');
        const closeScheduleModalBtn = document.getElementById('closeScheduleModalBtn');
        const printScheduleBtn = document.getElementById('printScheduleBtn');
        const scheduleModalContent = scheduleModal.querySelector('.modal-content');

        // View Schedule Function
        function viewSchedule(classroom) {
            const modalTitle = document.getElementById('scheduleModalTitle');
            const scheduleContent = document.getElementById('scheduleContent');
            const printTitle = document.getElementById('printTitle');
            const printSubtitle = document.getElementById('printSubtitle');
            const printDate = document.getElementById('printDate');

            // Set modal title and print information
            modalTitle.textContent = `Schedule - ${classroom.roomName}`;
            printTitle.textContent = `Classroom Schedule: ${classroom.roomName}`;
            printSubtitle.textContent = `${classroom.building} - ${classroom.department} (${classroom.college})`;
            printDate.textContent = `Generated on: ${new Date().toLocaleDateString()}`;

            // Show loading state
            scheduleContent.innerHTML = `
            <div class="flex justify-center items-center py-12">
                <div class="text-gray-500">Loading schedule...</div>
            </div>
        `;

            scheduleModal.classList.remove('hidden');
            scheduleModalContent.classList.remove('scale-95');
            scheduleModalContent.classList.add('scale-100');
            document.body.style.overflow = 'hidden';

            // Make API call to get real schedule data
            fetchScheduleData(classroom.roomId)
                .then(scheduleData => {
                    renderSchedule(scheduleData, classroom);
                })
                .catch(error => {
                    console.error('Error loading schedule:', error);
                    scheduleContent.innerHTML = `
                    <div class="no-schedule">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Error loading schedule</p>
                        <p class="text-sm mt-1">Unable to load schedule data. Please try again.</p>
                    </div>
                `;
                });
        }

        // Function to fetch real schedule data from backend
        async function fetchScheduleData(roomId) {
            const formData = new FormData();
            formData.append('action', 'get_classroom_schedule');
            formData.append('room_id', roomId);

            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load schedule');
            }

            return data.schedule;
        }

        // Render schedule in the modal
        function renderSchedule(schedule, classroom) {
            const scheduleContent = document.getElementById('scheduleContent');

            if (!schedule || Object.keys(schedule).length === 0) {
                scheduleContent.innerHTML = `
                <div class="no-schedule">
                    <i class="fas fa-calendar-times text-4xl mb-4"></i>
                    <p class="text-lg font-medium">No schedule available</p>
                    <p class="text-sm mt-1">This classroom has no scheduled classes for the current semester.</p>
                </div>
            `;
                return;
            }

            // Define day order for proper sorting
            const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            let html = '';

            // Sort days according to dayOrder
            const sortedDays = Object.keys(schedule).sort((a, b) => {
                return dayOrder.indexOf(a) - dayOrder.indexOf(b);
            });

            sortedDays.forEach(day => {
                html += `
                <div class="schedule-day">
                    <div class="schedule-day-header">
                        ${day}
                    </div>
                    ${schedule[day].map(session => `
                        <div class="schedule-session">
                            <div class="schedule-time">
                                <i class="fas fa-clock mr-2"></i>${session.time}
                            </div>
                            <div class="schedule-details">
                                <div class="schedule-item">
                                    <span class="schedule-label">Course:</span>
                                    ${session.course}
                                </div>
                                <div class="schedule-item">
                                    <span class="schedule-label">Section:</span>
                                    ${session.section}
                                </div>
                                <div class="schedule-item">
                                    <span class="schedule-label">Faculty:</span>
                                    ${session.faculty}
                                </div>
                                <div class="schedule-item">
                                    <span class="schedule-label">Type:</span>
                                    ${session.type}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            });

            scheduleContent.innerHTML = html;
        }

        // Print schedule function
        function printSchedule() {
            window.print();
        }

        // Close Schedule Modal
        const closeScheduleModal = () => {
            scheduleModalContent.classList.remove('scale-100');
            scheduleModalContent.classList.add('scale-95');
            setTimeout(() => {
                scheduleModal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 200);
        };

        // Event Listeners for Schedule Modal
        closeScheduleModalBtn.addEventListener('click', closeScheduleModal);
        printScheduleBtn.addEventListener('click', printSchedule);

        scheduleModal.addEventListener('click', (e) => {
            if (e.target === scheduleModal) closeScheduleModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !scheduleModal.classList.contains('hidden')) {
                closeScheduleModal();
            }
        });

        // Add event listeners for view schedule buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-schedule-btn')) {
                const button = e.target.closest('.view-schedule-btn');
                const classroom = {
                    roomId: button.dataset.roomId,
                    roomName: button.dataset.roomName,
                    building: button.dataset.building,
                    department: button.dataset.department,
                    college: button.dataset.college
                };
                viewSchedule(classroom);
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>