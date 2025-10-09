// Manual Schedule Management - COMPLETE FIX WITH SEMESTER FILTERING
let draggedElement = null;
let currentEditingId = null;
let currentSemesterCourses = {}; // Store courses for current semester only

function handleDragStart(e) {
  draggedElement = e.target;
  e.target.style.opacity = "0.5";
  e.dataTransfer.effectAllowed = "move";
}

function handleDragEnd(e) {
  e.target.style.opacity = "1";
  draggedElement = null;
}

function handleDragOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = "move";
}

function handleDragEnter(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.target.classList.add(
      "bg-yellow-100",
      "border-2",
      "border-dashed",
      "border-yellow-400"
    );
  }
}

function handleDragLeave(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.target.classList.remove(
      "bg-yellow-100",
      "border-2",
      "border-dashed",
      "border-yellow-400"
    );
  }
}

function handleDrop(e) {
  e.preventDefault();
  const dropZone = e.target.closest(".drop-zone");
  if (dropZone && draggedElement && dropZone !== draggedElement.parentElement) {
    dropZone.classList.remove(
      "bg-yellow-100",
      "border-2",
      "border-dashed",
      "border-yellow-400"
    );

    const scheduleId = draggedElement.dataset.scheduleId;
    const newDay = dropZone.dataset.day;
    const newStartTime = dropZone.dataset.startTime;
    const newEndTime = dropZone.dataset.endTime;

    const scheduleIndex = window.scheduleData.findIndex(
      (s) => s.schedule_id == scheduleId
    );
    if (scheduleIndex !== -1) {
      window.scheduleData[scheduleIndex].day_of_week = newDay;
      window.scheduleData[scheduleIndex].start_time = newStartTime + ":00";
      window.scheduleData[scheduleIndex].end_time = newEndTime + ":00";
    }

    const oldButton = draggedElement.parentElement.querySelector("button");
    if (oldButton) oldButton.style.display = "block";

    const existingCard = dropZone.querySelector(".schedule-card");
    if (existingCard && existingCard !== draggedElement) {
      draggedElement.parentElement.appendChild(existingCard);
    }

    const newButton = dropZone.querySelector("button");
    if (newButton) newButton.style.display = "none";

    dropZone.appendChild(draggedElement);
    showNotification(
      "Schedule moved successfully! Don't forget to save changes.",
      "success"
    );
  }
}

// Replace the existing openDeleteModal function
function openDeleteModal() {
    console.log('Opening delete modal...');
    const modal = document.getElementById('delete-confirmation-modal');
    
    if (!modal) {
        console.error('Delete confirmation modal element not found!');
        // Fallback to simple confirm dialog
        if (confirm('Are you sure you want to delete all schedules? This action cannot be undone.')) {
            confirmDeleteSchedules();
        }
        return;
    }
    
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    console.log('Modal should now be visible');
}

// Replace the existing closeDeleteModal function
function closeDeleteModal() {
    console.log('Closing delete modal...');
    const modal = document.getElementById('delete-confirmation-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

// Replace the existing deleteAllSchedules function
function deleteAllSchedules() {
    console.log('Delete all schedules function called');
    openDeleteModal();
}

// Replace the existing confirmDeleteSchedules function
function confirmDeleteSchedules() {
    console.log('Confirming delete schedules...');
    
    const deleteButton = document.querySelector('#delete-confirmation-modal button[onclick="confirmDeleteSchedules()"]');
    let originalText = '';
    
    if (deleteButton) {
        originalText = deleteButton.innerHTML;
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
        deleteButton.disabled = true;
    }

    fetch('/chair/generate-schedules', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'delete_schedules',
            confirm: 'true'
        }),
    })
    .then(response => {
        console.log('Delete response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Delete response data:', data);
        if (data.success) {
            showNotification('All schedules have been deleted successfully.', 'success');
            window.scheduleData = [];
            updateScheduleDisplay([]);
            
            const generationResults = document.getElementById('generation-results');
            if (generationResults) {
                generationResults.classList.add('hidden');
            }
        } else {
            showNotification('Error deleting schedules: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showNotification('Error deleting schedules: ' + error.message, 'error');
    })
    .finally(() => {
        if (deleteButton) {
            deleteButton.innerHTML = originalText;
            deleteButton.disabled = false;
        }
        closeDeleteModal();
    });
}

function deleteSchedule(scheduleId) {
  if (!confirm("Are you sure you want to delete this schedule?")) return;

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "delete_schedule",
      schedule_id: scheduleId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.scheduleData = window.scheduleData.filter(
          (s) => s.schedule_id != scheduleId
        );
        updateScheduleDisplay(window.scheduleData);
        showNotification("Schedule deleted successfully!", "success");
        initializeDragAndDrop();
      } else {
        showNotification(data.message || "Failed to delete schedule.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Error deleting schedule: " + error.message, "error");
    });
}

function deleteAllSchedules() {
  if (
    !confirm(
      "Are you sure you want to delete all schedules? This action cannot be undone."
    )
  )
    return;

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "delete_schedules",
      confirm: "true",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.scheduleData = [];
        updateScheduleDisplay(window.scheduleData);
        showNotification("All schedules deleted successfully!", "success");
        initializeDragAndDrop();
      } else {
        showNotification(
          data.message || "Failed to delete all schedules.",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "Error deleting all schedules: " + error.message,
        "error"
      );
    });
}

function initializeDragAndDrop() {
  const dropZones = document.querySelectorAll(".drop-zone");
  dropZones.forEach((zone) => {
    zone.addEventListener("dragover", handleDragOver);
    zone.addEventListener("dragenter", handleDragEnter);
    zone.addEventListener("dragleave", handleDragLeave);
    zone.addEventListener("drop", handleDrop);
  });
}

// Enhanced autoFillCourseName function in manual_schedules.js
function autoFillCourseName(courseCode) {
    const courseNameInput = document.getElementById('course-name');
    const courseCodesDatalist = document.getElementById('course-codes');
    
    if (!courseCode || !courseNameInput) return;
    
    // Find the option with matching course code
    const options = courseCodesDatalist.getElementsByTagName('option');
    let courseFound = false;
    
    for (let option of options) {
        if (option.value === courseCode) {
            courseNameInput.value = option.getAttribute('data-name') || '';
            const yearLevel = option.getAttribute('data-year-level');
            
            // Auto-filter sections based on course year level
            if (yearLevel) {
                autoFilterSections(yearLevel);
            }
            
            courseFound = true;
            break;
        }
    }
    
    if (!courseFound) {
        courseNameInput.value = '';
        // Reset section filter if course not found
        resetSectionFilter();
    }
}

// Enhanced section filtering based on curriculum year level
function autoFilterSections(yearLevel) {
    const sectionSelect = document.getElementById('section-name');
    if (!sectionSelect) return;
    
    // Show all options first
    const allOptions = sectionSelect.querySelectorAll('option');
    allOptions.forEach(option => {
        option.style.display = '';
    });
    
    // Hide options that don't match the year level
    // Convert year level format if needed (e.g., "1st Year" -> "1st")
    const searchYear = yearLevel.toLowerCase().replace(' year', '').trim();
    
    const optgroups = sectionSelect.querySelectorAll('optgroup');
    optgroups.forEach(optgroup => {
        const groupYearLevel = optgroup.label.toLowerCase().replace(' year', '').trim();
        if (groupYearLevel !== searchYear) {
            const options = optgroup.querySelectorAll('option');
            options.forEach(option => {
                option.style.display = 'none';
            });
        }
    });
    
    // Reset selection if current selection is hidden
    const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
    if (selectedOption.style.display === 'none') {
        sectionSelect.value = '';
    }
}

function resetSectionFilter() {
    const sectionSelect = document.getElementById('section-name');
    if (!sectionSelect) return;
    
    const allOptions = sectionSelect.querySelectorAll('option');
    allOptions.forEach(option => {
        option.style.display = '';
    });
}

// Extract year level from course code (customize this based on your course code format)
function extractYearLevelFromCourseCode(courseCode) {
    if (!courseCode) return null;
    
    // Example: CC101 -> 1st year, CC201 -> 2nd year, etc.
    const match = courseCode.match(/\d{3}/);
    if (match) {
        const codeNum = parseInt(match[0]);
        if (codeNum >= 100 && codeNum < 200) return '1st';
        if (codeNum >= 200 && codeNum < 300) return '2nd';
        if (codeNum >= 300 && codeNum < 400) return '3rd';
        if (codeNum >= 400 && codeNum < 500) return '4th';
    }
    
    return null;
}

// Update time fields when dropdowns change
function updateTimeFields() {
    const startTimeSelect = document.getElementById('start-time');
    const endTimeSelect = document.getElementById('end-time');
    const modalStartTime = document.getElementById('modal-start-time');
    const modalEndTime = document.getElementById('modal-end-time');
    
    if (startTimeSelect && modalStartTime) {
        modalStartTime.value = startTimeSelect.value + ':00';
    }
    
    if (endTimeSelect && modalEndTime) {
        modalEndTime.value = endTimeSelect.value + ':00';
    }
}

// Update day field when pattern changes
function updateDayField() {
    const daySelect = document.getElementById('day-select');
    const modalDay = document.getElementById('modal-day');
    
    if (daySelect && modalDay) {
        modalDay.value = daySelect.value;
    }
}

// Enhanced handleScheduleSubmit with conflict detection display
function handleScheduleSubmit(e) {
    e.preventDefault();
    console.log("Submitting schedule form...");

    // Verify we're working with current semester
    const currentSemesterId = window.currentSemester?.semester_id;
    if (!currentSemesterId) {
        showNotification("No active semester selected", "error");
        return;
    }

    // Get all form values
    const formData = new FormData(document.getElementById('schedule-form'));
    const data = {
        schedule_id: formData.get('schedule_id'),
        course_code: formData.get('course_code').trim(),
        course_name: formData.get('course_name').trim(),
        section_name: formData.get('section_name').trim(),
        faculty_name: formData.get('faculty_name').trim(),
        room_name: formData.get('room_name').trim() || 'Online',
        day_of_week: formData.get('day_of_week'),
        start_time: formData.get('start_time'),
        end_time: formData.get('end_time'),
        schedule_type: formData.get('schedule_type') || 'f2f',
        semester_id: currentSemesterId
    };

    console.log("Form data:", data);

    // Validate required fields
    if (!data.course_code || !data.course_name || !data.section_name || 
        !data.faculty_name || !data.day_of_week || !data.start_time || !data.end_time) {
        showNotification("Please fill out all required fields.", "error");
        return;
    }

    // Validate time logic
    if (data.start_time >= data.end_time) {
        showNotification("End time must be after start time.", "error");
        return;
    }

    // Determine if adding or updating
    const isUpdate = data.schedule_id && data.schedule_id !== "";
    const url = isUpdate ? "/chair/updateSchedule" : "/chair/addSchedule";

    console.log("Submitting to:", url, "isUpdate:", isUpdate);

    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    submitButton.disabled = true;

    // Submit form
    fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(data),
    })
    .then((response) => {
        console.log("Response status:", response.status);
        return response.json();
    })
    .then((result) => {
        console.log("Save response:", result);

        if (result.success) {
            closeModal();
            showNotification(
                isUpdate ? "Schedule updated successfully!" : "Schedule added successfully!",
                "success"
            );

            // Update local schedule data
            if (isUpdate) {
                const index = window.scheduleData.findIndex(
                    (s) => s.schedule_id == data.schedule_id
                );
                if (index !== -1) {
                    window.scheduleData[index] = {
                        ...window.scheduleData[index],
                        ...result.schedule,
                        semester_id: currentSemesterId,
                    };
                }
            } else {
                window.scheduleData.push({
                    ...result.schedule,
                    semester_id: currentSemesterId,
                });
            }

            // Refresh display
            updateScheduleDisplay(window.scheduleData);
            initializeDragAndDrop();

            // Rebuild course mappings with new data
            buildCurrentSemesterCourseMappings();
        } else {
            // Show conflicts in a user-friendly way
            if (result.conflicts && result.conflicts.length > 0) {
                const conflictDetails = result.conflicts.join('\n• ');
                showNotification(
                    `Schedule conflicts detected:\n• ${conflictDetails}`,
                    "error",
                    10000 // Show for 10 seconds
                );
            } else {
                showNotification(result.message || "Failed to save schedule.", "error");
            }
        }
    })
    .catch((error) => {
        console.error("Error saving schedule:", error);
        showNotification("Error saving schedule: " + error.message, "error");
    })
    .finally(() => {
        // Restore button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// Enhanced showNotification function with longer duration support
function showNotification(message, type = 'success', duration = 5000) {
    // Remove existing notification
    const existingNotification = document.getElementById('notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    const notificationDiv = document.createElement('div');
    notificationDiv.id = 'notification';
    notificationDiv.className = `fixed top-4 right-4 z-50 max-w-sm w-full ${
        type === 'success' ? 'bg-green-50 border border-green-200' : 
        type === 'error' ? 'bg-red-50 border border-red-200' :
        'bg-yellow-50 border border-yellow-200'
    } rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full`;
    
    notificationDiv.innerHTML = `
        <div class="flex p-4">
            <div class="flex-shrink-0">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle text-green-400' : 
                    type === 'error' ? 'fa-exclamation-circle text-red-400' :
                    'fa-exclamation-triangle text-yellow-400'
                } text-lg"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium ${
                    type === 'success' ? 'text-green-800' : 
                    type === 'error' ? 'text-red-800' :
                    'text-yellow-800'
                } whitespace-pre-line">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button class="inline-flex ${
                    type === 'success' ? 'text-green-400 hover:text-green-600' : 
                    type === 'error' ? 'text-red-400 hover:text-red-600' :
                    'text-yellow-400 hover:text-yellow-600'
                }" onclick="this.parentElement.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(notificationDiv);

    // Animate in
    setTimeout(() => {
        notificationDiv.classList.remove('translate-x-full');
        notificationDiv.classList.add('translate-x-0');
    }, 100);

    // Auto remove after duration
    setTimeout(() => {
        if (notificationDiv.parentElement) {
            notificationDiv.classList.add('translate-x-full');
            setTimeout(() => {
                if (notificationDiv.parentElement) {
                    notificationDiv.remove();
                }
            }, 300);
        }
    }, duration);
}

// Initialize the enhanced modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Set up time field synchronization
    updateTimeFields();
    updateDayField();
    
    // Add event listeners for real-time updates
    const startTimeSelect = document.getElementById('start-time');
    const endTimeSelect = document.getElementById('end-time');
    const daySelect = document.getElementById('day-select');
    
    if (startTimeSelect) startTimeSelect.addEventListener('change', updateTimeFields);
    if (endTimeSelect) endTimeSelect.addEventListener('change', updateTimeFields);
    if (daySelect) daySelect.addEventListener('change', updateDayField);
    
    console.log("Enhanced manual schedules initialized");
});

// Build course mappings from curriculum courses for current semester
function buildCurrentSemesterCourseMappings() {
    currentSemesterCourses = {};

    console.log("Building course mappings for current semester:", window.currentSemester);

    // Use curriculum courses from window.curriculumCourses
    if (window.curriculumCourses && window.curriculumCourses.length > 0) {
        console.log("Using curriculum courses:", window.curriculumCourses.length);
        
        window.curriculumCourses.forEach(course => {
            if (course.course_code && course.course_name) {
                currentSemesterCourses[course.course_code.trim().toUpperCase()] = {
                    code: course.course_code,
                    name: course.course_name,
                    course_id: course.course_id,
                    year_level: course.curriculum_year,
                    semester: course.curriculum_semester,
                    units: course.units,
                    lecture_units: course.lecture_units,
                    lab_units: course.lab_units
                };
            }
        });
        
        console.log("Course mappings built:", Object.keys(currentSemesterCourses).length, "unique courses");
        console.log("Sample courses:", Object.values(currentSemesterCourses).slice(0, 3));
    } else {
        console.warn("No curriculum courses found! Check if curriculum is set up correctly.");
        
        // Fallback: Get unique courses from current semester schedules
        const currentSemesterId = window.currentSemester?.semester_id;
        
        if (currentSemesterId) {
            const currentSemesterSchedules = window.scheduleData.filter(schedule => {
                return schedule.semester_id == currentSemesterId;
            });

            console.log("Fallback: Using", currentSemesterSchedules.length, "schedules for current semester");

            currentSemesterSchedules.forEach(schedule => {
                if (schedule.course_code && schedule.course_name) {
                    currentSemesterCourses[schedule.course_code.trim().toUpperCase()] = {
                        code: schedule.course_code,
                        name: schedule.course_name,
                        course_id: schedule.course_id
                    };
                }
            });
        }
    }
}

// FIXED: Sync course name when code is typed (case-insensitive)
function syncCourseName() {
  const courseCodeInput = document.getElementById("course-code");
  const courseNameInput = document.getElementById("course-name");

  if (!courseCodeInput || !courseNameInput) return;

  const enteredCode = courseCodeInput.value.trim().toUpperCase();

  console.log("Looking up course:", enteredCode);

  // Check if this code exists in our current semester mapping
  if (currentSemesterCourses[enteredCode]) {
    const course = currentSemesterCourses[enteredCode];
    courseNameInput.value = course.name;
    console.log("Found course:", course);
  } else {
    console.log("Course not found in current semester");
  }
}

// FIXED: Sync course code when name is typed
function syncCourseCode() {
  const courseCodeInput = document.getElementById("course-code");
  const courseNameInput = document.getElementById("course-name");

  if (!courseCodeInput || !courseNameInput) return;

  const enteredName = courseNameInput.value.trim().toLowerCase();

  // Search through courses to find matching name
  const matchingCourse = Object.values(currentSemesterCourses).find(
    (course) => course.name.toLowerCase() === enteredName
  );

  if (matchingCourse) {
    courseCodeInput.value = matchingCourse.code;
    console.log("Found matching course code:", matchingCourse.code);
  }
}

// FIXED: Open add modal with current semester data
function openAddModal() {
  console.log(
    "Opening add modal for current semester:",
    window.currentSemester
  );

  // Rebuild course mappings to ensure we have latest data
  buildCurrentSemesterCourseMappings();

  // Reset form
  const form = document.getElementById("schedule-form");
  if (form) form.reset();

  // Set modal title
  document.getElementById("modal-title").textContent = "Add Schedule";

  // Clear all fields
  document.getElementById("schedule-id").value = "";
  document.getElementById("modal-day").value = "Monday";
  document.getElementById("modal-start-time").value = "07:30";
  document.getElementById("modal-end-time").value = "08:30";
  document.getElementById("course-code").value = "";
  document.getElementById("course-name").value = "";
  document.getElementById("faculty-name").value = "";
  document.getElementById("room-name").value = "";
  document.getElementById("section-name").value = "";

  // Set visible dropdowns
  document.getElementById("day-select").value = "Monday";
  document.getElementById("start-time").value = "07:30";
  document.getElementById("end-time").value = "08:30";

  currentEditingId = null;

  // Show modal
  showModal();
}

// FIXED: Open add modal for specific time slot
function openAddModalForSlot(day, startTime, endTime) {
  console.log("Opening add modal for slot:", day, startTime, endTime);

  // First open the modal normally
  openAddModal();

  // Then set the specific slot data
  document.getElementById("modal-day").value = day;
  document.getElementById("modal-start-time").value = startTime;
  document.getElementById("modal-end-time").value = endTime;

  // Update visible dropdowns
  document.getElementById("day-select").value = day;
  document.getElementById("start-time").value = startTime;
  document.getElementById("end-time").value = endTime;
}

// FIXED: Edit schedule with proper data loading
function editSchedule(scheduleId) {
  console.log("Editing schedule:", scheduleId);

  // Find schedule in current semester data
  const schedule = window.scheduleData.find((s) => s.schedule_id == scheduleId);

  if (!schedule) {
    console.error("Schedule not found:", scheduleId);
    showNotification("Schedule not found", "error");
    return;
  }

  console.log("Found schedule:", schedule);

  // Verify it's from current semester
  if (schedule.semester_id != window.currentSemester?.semester_id) {
    showNotification("Can only edit schedules from current semester", "error");
    return;
  }

  // Rebuild course mappings
  buildCurrentSemesterCourseMappings();

  // Set modal title
  document.getElementById("modal-title").textContent = "Edit Schedule";

  // Set schedule ID
  document.getElementById("schedule-id").value = schedule.schedule_id;

  // Set course info
  document.getElementById("course-code").value = schedule.course_code || "";
  document.getElementById("course-name").value = schedule.course_name || "";

  // Set faculty
  document.getElementById("faculty-name").value = schedule.faculty_name || "";

  // Set room
  document.getElementById("room-name").value = schedule.room_name || "";

  // Set section
  document.getElementById("section-name").value = schedule.section_name || "";

  // Set day
  const day = schedule.day_of_week || "Monday";
  document.getElementById("modal-day").value = day;
  document.getElementById("day-select").value = day;

  // Set time
  const startTime = schedule.start_time
    ? schedule.start_time.substring(0, 5)
    : "07:30";
  const endTime = schedule.end_time
    ? schedule.end_time.substring(0, 5)
    : "08:30";

  document.getElementById("modal-start-time").value = startTime;
  document.getElementById("modal-end-time").value = endTime;
  document.getElementById("start-time").value = startTime;
  document.getElementById("end-time").value = endTime;

  currentEditingId = scheduleId;

  // Show modal
  showModal();
}

function showModal() {
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
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
  }

  const form = document.getElementById("schedule-form");
  if (form) form.reset();

  currentEditingId = null;
}

// FIXED: Handle schedule form submission with semester validation
function handleScheduleSubmit(e) {
  e.preventDefault();
  console.log("Submitting schedule form...");

  // Verify we're working with current semester
  const currentSemesterId = window.currentSemester?.semester_id;
  if (!currentSemesterId) {
    showNotification("No active semester selected", "error");
    return;
  }

  // Get all form values
  const data = {
    schedule_id: document.getElementById("schedule-id").value,
    course_code: document.getElementById("course-code").value.trim(),
    course_name: document.getElementById("course-name").value.trim(),
    section_name: document.getElementById("section-name").value.trim(),
    faculty_name: document.getElementById("faculty-name").value.trim(),
    room_name: document.getElementById("room-name").value.trim() || "Online",
    day_of_week: document.getElementById("modal-day").value,
    start_time: document.getElementById("modal-start-time").value + ":00",
    end_time: document.getElementById("modal-end-time").value + ":00",
    semester_id: currentSemesterId,
  };

  console.log("Form data:", data);

  // Validate required fields
  if (
    !data.course_code ||
    !data.course_name ||
    !data.section_name ||
    !data.faculty_name ||
    !data.day_of_week ||
    !data.start_time ||
    !data.end_time
  ) {
    showNotification("Please fill out all required fields.", "error");
    return;
  }

  // Determine if adding or updating
  const isUpdate = currentEditingId !== null && currentEditingId !== "";
  const url = isUpdate ? "/chair/updateSchedule" : "/chair/addSchedule";

  console.log("Submitting to:", url, "isUpdate:", isUpdate);

  // Submit form
  fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams(data),
  })
    .then((response) => {
      console.log("Response status:", response.status);
      return response.json();
    })
    .then((result) => {
      console.log("Save response:", result);

      if (result.success) {
        closeModal();
        showNotification(
          isUpdate
            ? "Schedule updated successfully!"
            : "Schedule added successfully!",
          "success"
        );

        // Update local schedule data
        if (isUpdate) {
          const index = window.scheduleData.findIndex(
            (s) => s.schedule_id == currentEditingId
          );
          if (index !== -1) {
            window.scheduleData[index] = {
              ...window.scheduleData[index],
              ...result.schedule,
              semester_id: currentSemesterId, // Ensure semester ID is set
            };
          }
        } else {
          window.scheduleData.push({
            ...result.schedule,
            semester_id: currentSemesterId, // Ensure semester ID is set
          });
        }

        // Refresh display
        updateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();

        // Rebuild course mappings with new data
        buildCurrentSemesterCourseMappings();
      } else {
        showNotification(result.message || "Failed to save schedule.", "error");

        // Show conflicts if available
        if (result.conflicts && result.conflicts.length > 0) {
          const conflictMsg = "Conflicts:\n" + result.conflicts.join("\n");
          alert(conflictMsg);
        }
      }
    })
    .catch((error) => {
      console.error("Error saving schedule:", error);
      showNotification("Error saving schedule: " + error.message, "error");
    });
}

function saveAllChanges() {
  const currentSemesterId = window.currentSemester?.semester_id;

  // Filter to only current semester schedules
  const currentSemesterSchedules = window.scheduleData.filter(
    (s) => s.semester_id == currentSemesterId
  );

  const updatedSchedules = currentSemesterSchedules.map((schedule) => ({
    schedule_id: schedule.schedule_id,
    day_of_week: schedule.day_of_week,
    start_time: schedule.start_time,
    end_time: schedule.end_time,
    course_code: schedule.course_code,
    course_name: schedule.course_name,
    faculty_name: schedule.faculty_name,
    room_name: schedule.room_name || "",
    section_name: schedule.section_name,
    semester_id: schedule.semester_id,
  }));

  fetch("/chair/schedule_management", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ schedules: updatedSchedules }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("All changes saved successfully!", "success");
        window.scheduleData = data.schedules || [];
        updateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();
      } else {
        showNotification(data.message || "Failed to save changes.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Error saving changes: " + error.message, "error");
    });
}

function deleteSchedule(scheduleId) {
  if (!confirm("Are you sure you want to delete this schedule?")) return;

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "delete_schedule",
      schedule_id: scheduleId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.scheduleData = window.scheduleData.filter(
          (s) => s.schedule_id != scheduleId
        );
        updateScheduleDisplay(window.scheduleData);
        showNotification("Schedule deleted successfully!", "success");
        initializeDragAndDrop();
        buildCurrentSemesterCourseMappings(); // Rebuild after deletion
      } else {
        showNotification(data.message || "Failed to delete schedule.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Error deleting schedule: " + error.message, "error");
    });
}

function openDeleteModal() {
  const modal = document.getElementById("delete-confirmation-modal");
  if (!modal) {
    if (
      confirm(
        "Are you sure you want to delete all schedules? This action cannot be undone."
      )
    ) {
      confirmDeleteSchedules();
    }
    return;
  }
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeDeleteModal() {
  const modal = document.getElementById("delete-confirmation-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

function deleteAllSchedules() {
  openDeleteModal();
}

function confirmDeleteSchedules() {
  const deleteButton = document.querySelector(
    '#delete-confirmation-modal button[onclick="confirmDeleteSchedules()"]'
  );
  let originalText = "";

  if (deleteButton) {
    originalText = deleteButton.innerHTML;
    deleteButton.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteButton.disabled = true;
  }

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "delete_schedules",
      confirm: "true",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(
          "All schedules have been deleted successfully.",
          "success"
        );
        window.scheduleData = [];
        updateScheduleDisplay([]);
        buildCurrentSemesterCourseMappings(); // Rebuild after deletion

        const generationResults = document.getElementById("generation-results");
        if (generationResults) {
          generationResults.classList.add("hidden");
        }
      } else {
        showNotification(
          "Error deleting schedules: " + (data.message || "Unknown error"),
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Delete error:", error);
      showNotification("Error deleting schedules: " + error.message, "error");
    })
    .finally(() => {
      if (deleteButton) {
        deleteButton.innerHTML = originalText;
        deleteButton.disabled = false;
      }
      closeDeleteModal();
    });
}

function filterSchedulesManual() {
  const yearLevel = document.getElementById("filter-year-manual").value;
  const section = document.getElementById("filter-section-manual").value;
  const room = document.getElementById("filter-room-manual").value;
  const scheduleCells = document.querySelectorAll("#schedule-grid .drop-zone");

  scheduleCells.forEach((cell) => {
    const card = cell.querySelector(".schedule-card");
    if (card) {
      const itemYearLevel = card.dataset.yearLevel;
      const itemSectionName = card.dataset.sectionName;
      const itemRoomName = card.dataset.roomName;
      const matchesYear = !yearLevel || itemYearLevel === yearLevel;
      const matchesSection = !section || itemSectionName === section;
      const matchesRoom = !room || itemRoomName === room;

      card.style.display =
        matchesYear && matchesSection && matchesRoom ? "block" : "none";
    }

    const addButton = cell.querySelector("button");
    if (addButton) {
      addButton.style.display =
        !card || card.style.display === "none" ? "block" : "none";
    }
  });
}

function clearFiltersManual() {
  document.getElementById("filter-year-manual").value = "";
  document.getElementById("filter-section-manual").value = "";
  document.getElementById("filter-room-manual").value = "";
  filterSchedulesManual();
}

function refreshManualView() {
  location.reload();
}

// Initialize event listeners
document.addEventListener("DOMContentLoaded", function () {
  console.log("Manual schedules JS loaded");
  console.log("Current semester:", window.currentSemester);
  console.log("Schedule data count:", window.scheduleData?.length || 0);

  // Build initial course mappings from current semester
  buildCurrentSemesterCourseMappings();

  // Initialize drag-and-drop
  initializeDragAndDrop();

  // Modal click outside to close
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  }

  // Delete modal click outside to close
  const deleteModal = document.getElementById("delete-confirmation-modal");
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) {
        closeDeleteModal();
      }
    });
  }

  console.log("Manual schedules initialized successfully");
  console.log(
    "Available courses for current semester:",
    Object.keys(currentSemesterCourses).length
  );
});


// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize drag-and-drop
    initializeDragAndDrop();

    // Add event listener for add schedule button
    const addScheduleBtn = document.getElementById('add-schedule-btn');
    if (addScheduleBtn) addScheduleBtn.addEventListener('click', openAddModal);

    // Add event listener for save changes button
    const saveChangesBtn = document.getElementById('save-changes-btn');
    if (saveChangesBtn) saveChangesBtn.addEventListener('click', saveAllChanges);

    // Add event listener for delete all button
    const deleteAllBtn = document.getElementById('delete-all-btn');
    if (deleteAllBtn) deleteAllBtn.addEventListener('click', deleteAllSchedules);

    // Add event listeners for filter dropdowns
    const filterYear = document.getElementById('filter-year-manual');
    if (filterYear) filterYear.addEventListener('change', filterSchedulesManual);

    const filterSection = document.getElementById('filter-section-manual');
    if (filterSection) filterSection.addEventListener('change', filterSchedulesManual);

    const filterRoom = document.getElementById('filter-room-manual');
    if (filterRoom) filterRoom.addEventListener('change', filterSchedulesManual);

    // Add event listeners for course code and name sync
    const courseCodeInput = document.getElementById('course-code');
    if (courseCodeInput) courseCodeInput.addEventListener('input', syncCourseName);

    const courseNameInput = document.getElementById('course-name');
    if (courseNameInput) courseNameInput.addEventListener('input', syncCourseCode);
});