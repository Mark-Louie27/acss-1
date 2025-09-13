
        // Global variables
        let scheduleData = <?php echo json_encode($schedules ?? []); ?>;
        let currentEditingId = null;
        let draggedElement = null;

        // Pass PHP data to JavaScript
        const departmentId = <?php echo json_encode($departmentId ?? null); ?>;
        const currentSemester = <?php echo json_encode($currentSemester ?? null); ?>;
        const rawSectionsData = <?php echo json_encode($sections ?? []); ?>;
        const currentAcademicYear = "<?php echo htmlspecialchars($currentSemester['academic_year'] ?? ''); ?>";

        // Transform sections data
        const sectionsData = Array.isArray(rawSectionsData) ? rawSectionsData.map((s, index) => ({
            section_id: s.section_id ?? (index + 1),
            section_name: s.section_name ?? '',
            year_level: s.year_level ?? 'Unknown',
            curriculum_id: s.curriculum_id ?? null,
            academic_year: s.academic_year ?? ''
        })) : [];

        // Tab switching function with improved animation
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('bg-yellow-500', 'text-white');
                btn.classList.add('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
            });
            
            const activeTab = document.getElementById(`tab-${tabName}`);
            if (activeTab) {
                activeTab.classList.add('bg-yellow-500', 'text-white');
                activeTab.classList.remove('text-gray-700', 'hover:text-gray-900', 'hover:bg-gray-100');
            }

            // Show/hide content with fade effect
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            const targetContent = document.getElementById(`content-${tabName}`);
            if (targetContent) {
                targetContent.classList.remove('hidden');
            }

            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName === 'schedule' ? 'schedule-list' : tabName);
            window.history.pushState({}, '', url);
        }

        // Enhanced modal functions
        function openAddModal() {
            const modal = document.getElementById('schedule-modal');
            const modalContent = document.getElementById('modal-content');
            
            document.getElementById('modal-title').textContent = 'Add Schedule';
            document.getElementById('schedule-form').reset();
            document.getElementById('schedule-id').value = '';
            document.getElementById('delete-schedule-btn').classList.add('hidden');
            currentEditingId = null;
            
            modal.classList.remove('hidden');
            
            // Trigger animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function openAddModalForSlot(day, startTime, endTime) {
            openAddModal();
            
            // Set the slot information
            document.getElementById('modal-day').value = day;
            document.getElementById('modal-start-time').value = startTime;
            document.getElementById('modal-end-time').value = endTime;
            document.getElementById('day-select').value = day;
            document.getElementById('time-slot').value = `${startTime}-${endTime}`;
        }

        function editSchedule(scheduleId) {
            const schedule = scheduleData.find(s => s.schedule_id == scheduleId);
            if (schedule) {
                const modal = document.getElementById('schedule-modal');
                const modalContent = document.getElementById('modal-content');
                
                document.getElementById('modal-title').textContent = 'Edit Schedule';
                document.getElementById('schedule-id').value = schedule.schedule_id;
                document.getElementById('course-code').value = schedule.course_code;
                document.getElementById('course-name').value = schedule.course_name;
                document.getElementById('faculty-name').value = schedule.faculty_name;
                document.getElementById('room-name').value = schedule.room_name || '';
                document.getElementById('section-name').value = schedule.section_name;
                document.getElementById('modal-day').value = schedule.day_of_week;
                document.getElementById('modal-start-time').value = schedule.start_time.substring(0, 5);
                document.getElementById('modal-end-time').value = schedule.end_time.substring(0, 5);
                document.getElementById('day-select').value = schedule.day_of_week;
                document.getElementById('time-slot').value = `${schedule.start_time.substring(0, 5)}-${schedule.end_time.substring(0, 5)}`;
                document.getElementById('delete-schedule-btn').classList.remove('hidden');
                
                currentEditingId = scheduleId;
                modal.classList.remove('hidden');
                
                // Trigger animation
                setTimeout(() => {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);
            }
        }

        function closeModal() {
            const modal = document.getElementById('schedule-modal');
            const modalContent = document.getElementById('modal-content');
            
            modalContent.classList.add('scale-95', 'opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                currentEditingId = null;
            }, 200);
        }

        function confirmDeleteSchedule() {
            if (currentEditingId && confirm('Are you sure you want to delete this schedule?')) {
                deleteSchedule(currentEditingId);
                closeModal();
            }
        }

        // Enhanced filter functions with proper selectors
        function filterSchedules() {
            const yearLevel = document.getElementById('filter-year').value;
            const section = document.getElementById('filter-section').value;
            const room = document.getElementById('filter-room').value;
            
            // Use correct selector for schedule items in view tab
            const scheduleItems = document.querySelectorAll('#timetableGrid .schedule-item');

            scheduleItems.forEach(item => {
                const itemYearLevel = item.getAttribute('data-year-level');
                const itemSectionName = item.getAttribute('data-section-name');
                const itemRoomName = item.getAttribute('data-room-name');
                
                const matchesYear = !yearLevel || itemYearLevel === yearLevel;
                const matchesSection = !section || itemSectionName === section;
                const matchesRoom = !room || itemRoomName === room;

                if (matchesYear && matchesSection && matchesRoom) {
                    item.style.display = 'block';
                    item.parentElement.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    // Check if parent has any visible items
                    const siblingItems = item.parentElement.querySelectorAll('.schedule-item');
                    const hasVisibleSiblings = Array.from(siblingItems).some(sibling => 
                        sibling.style.display !== 'none'
                    );
                    if (!hasVisibleSiblings) {
                        item.parentElement.style.display = 'none';
                    }
                }
            });
        }

        function filterSchedulesManual() {
            const yearLevel = document.getElementById('filter-year-manual').value;
            const section = document.getElementById('filter-section-manual').value;
            const room = document.getElementById('filter-room-manual').value;
            
            // Use correct selector for schedule cards in manual edit tab
            const scheduleItems = document.querySelectorAll('#schedule-grid .schedule-card');

            scheduleItems.forEach(item => {
                const itemYearLevel = item.getAttribute('data-year-level');
                const itemSectionName = item.getAttribute('data-section-name');
                const itemRoomName = item.getAttribute('data-room-name');
                
                const matchesYear = !yearLevel || itemYearLevel === yearLevel;
                const matchesSection = !section || itemSectionName === section;
                const matchesRoom = !room || itemRoomName === room;

                if (matchesYear && matchesSection && matchesRoom) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function clearFilters() {
            document.getElementById('filter-year').value = '';
            document.getElementById('filter-section').value = '';
            document.getElementById('filter-room').value = '';
            filterSchedules();
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

        // Other existing functions continue...
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
                    const order = { '1': 1, '2': 2, '3': 3, '4': 4 };
                    return (order[a[0]] || 99) - (order[b[0]] || 99);
                });

                uniqueYears.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearLevelsSelect.appendChild(option);
                });

                // Automatically select all year levels
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

                // Automatically select all sections
                for (let i = 0; i < sectionsSelect.options.length; i++) {
                    sectionsSelect.options[i].selected = true;
                }
            }
        }

        function generateSchedules() {
            const form = document.getElementById('generate-form');
            const formData = new FormData(form);

            const curriculumId = formData.get('curriculum_id');
            const selectedYearLevels = formData.getAll('year_levels[]');
            const selectedSections = formData.getAll('sections[]');

            if (!curriculumId) {
                showNotification('Please select a curriculum', 'error');
                return;
            }
            if (selectedYearLevels.length === 0 && selectedSections.length === 0) {
                showNotification('Please select at least one year level or section', 'error');
                return;
            }

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
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('loading-overlay').classList.add('hidden');
                    if (data.success) {
                        scheduleData = data.schedules || [];
                        document.getElementById('generation-results').classList.remove('hidden');
                        document.getElementById('total-courses').textContent = data.schedules ? data.schedules.length : 0;
                        document.getElementById('total-sections').textContent = new Set(data.schedules?.map(s => s.section_name)).size || 0;

                        updateScheduleDisplay(scheduleData);
                        showNotification('Schedules generated successfully!', 'success');
                    } else {
                        showNotification('Error: ' + (data.message || 'Failed to generate schedules'), 'error');
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').classList.add('hidden');
                    console.error('Error:', error);
                    showNotification('Error generating schedules: ' + error.message, 'error');
                });
        }

        // Additional helper functions...
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

        function deleteSchedule(scheduleId) {
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
                        showNotification('Schedule deleted successfully!', 'success');
                        location.reload();
                    } else {
                        showNotification('Error deleting schedule', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error deleting schedule', 'error');
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

        function showNotification(message, type = 'success') {
            // Create or update notification
            let notification = document.getElementById('dynamic-notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'dynamic-notification';
                notification.className = 'mb-6';
                document.querySelector('.max-w-7xl').insertBefore(notification, document.querySelector('.max-w-7xl').firstElementChild.nextElementSibling);
            }

            const bgColor = type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
            const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
            const iconColor = type === 'success' ? 'text-green-500' : 'text-red-500';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            notification.innerHTML = `
                <div class="flex items-center p-4 ${bgColor} border rounded-lg">
                    <i class="fas ${icon} ${iconColor} text-xl mr-3"></i>
                    <p class="text-sm font-medium ${textColor}">${message}</p>
                    <button onclick="hideNotification('dynamic-notification')" class="ml-auto ${iconColor} hover:opacity-75">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            notification.classList.remove('hidden');

            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideNotification('dynamic-notification');
            }, 5000);
        }

        function hideNotification(notificationId) {
            const notification = document.getElementById(notificationId);
            if (notification) {
                notification.classList.add('hidden');
            }
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

                // Move the element
                const scheduleId = draggedElement.dataset.scheduleId;
                const newDay = dropZone.dataset.day;
                const newStartTime = dropZone.dataset.startTime;
                const newEndTime = dropZone.dataset.endTime;

                // Update schedule data
                const scheduleIndex = scheduleData.findIndex(s => s.schedule_id == scheduleId);
                if (scheduleIndex !== -1) {
                    scheduleData[scheduleIndex].day_of_week = newDay;
                    scheduleData[scheduleIndex].start_time = newStartTime + ':00';
                    scheduleData[scheduleIndex].end_time = newEndTime + ':00';
                }

                // Clear the old position
                const oldButton = draggedElement.parentElement.querySelector('button');
                if (oldButton) {
                    oldButton.style.display = 'block';
                }

                // Move to new position
                const existingCard = dropZone.querySelector('.schedule-card');
                if (existingCard && existingCard !== draggedElement) {
                    // Swap positions - move existing card to old position
                    draggedElement.parentElement.appendChild(existingCard);
                }

                // Hide the add button in new position
                const newButton = dropZone.querySelector('button');
                if (newButton) {
                    newButton.style.display = 'none';
                }

                dropZone.appendChild(draggedElement);

                // Show success message
                showNotification('Schedule moved successfully! Don\'t forget to save changes.', 'success');
            }
        }

        // Initialize drag and drop
        function initializeDragAndDrop() {
            const dropZones = document.querySelectorAll('.drop-zone');
            dropZones.forEach(zone => {
                zone.addEventListener('dragover', handleDragOver);
                zone.addEventListener('dragenter', handleDragEnter);
                zone.addEventListener('dragleave', handleDragLeave);
                zone.addEventListener('drop', handleDrop);
            });
        }

        function updateScheduleDisplay(schedules) {
            scheduleData = schedules;

            // Update Manual Edit tab
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
                            s.start_time.substring(0, 5) === time[0]
                        );
                        
                        if (schedule) {
                            cell.innerHTML = `<div class="schedule-card bg-white border-l-4 border-yellow-500 rounded-lg p-3 shadow-sm draggable cursor-move" 
                                draggable="true" data-schedule-id="${escapeHtml(schedule.schedule_id)}" 
                                ondragstart="handleDragStart(event)" ondragend="handleDragEnd(event)"
                                data-year-level="${escapeHtml(schedule.year_level)}"
                                data-section-name="${escapeHtml(schedule.section_name)}"
                                data-room-name="${escapeHtml(schedule.room_name ?? 'Online')}">
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
                            cell.innerHTML = `<button onclick="openAddModalForSlot('${day}', '${time[0]}', '${time[1]}')" 
                                class="w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print">
                                <i class="fas fa-plus text-lg"></i>
                            </button>`;
                        }
                        row.appendChild(cell);
                    });
                    manualGrid.appendChild(row);
                });
                initializeDragAndDrop();
            }

            // Update View Schedule tab
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
                        cell.className = 'px-2 py-3 border-r border-gray-200 last:border-r-0';
                        
                        const daySchedules = schedules.filter(s =>
                            s.day_of_week === day &&
                            s.start_time.substring(0, 5) === time[0]
                        );
                        
                        if (daySchedules.length > 0) {
                            daySchedules.forEach(schedule => {
                                const colorClass = ['bg-blue-100 border-blue-300', 'bg-green-100 border-green-300', 
                                                 'bg-purple-100 border-purple-300', 'bg-orange-100 border-orange-300', 
                                                 'bg-pink-100 border-pink-300'][Math.floor(Math.random() * 5)] + ' border-l-4';
                                cell.innerHTML += `<div class="${colorClass} p-2 rounded-lg mb-1 schedule-item" 
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

        // Helper functions
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
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('printDropdown');
            const button = document.getElementById('printDropdownBtn');
            if (!dropdown.contains(e.target) && !button.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('schedule-modal');
            const modalContent = document.getElementById('modal-content');
            if (modal && !modal.classList.contains('hidden') && !modalContent.contains(e.target)) {
                closeModal();
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();

            // Set up generate button
            const generateBtn = document.getElementById('generate-btn');
            if (generateBtn) {
                generateBtn.addEventListener('click', generateSchedules);
            }

            // Handle URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'schedule-list') {
                switchTab('schedule');
            } else if (tab === 'manual') {
                switchTab('manual');
            } else if (tab === 'generate') {
                switchTab('generate');
            }

            // Initialize schedules display if data exists
            if (scheduleData && scheduleData.length > 0) {
                updateScheduleDisplay(scheduleData);
            }
        });