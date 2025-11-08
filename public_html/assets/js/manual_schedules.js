// Manual Schedule Management - CLEANED VERSION
let draggedElement = null;
let currentEditingId = null;
let currentSemesterCourses = {};
let validationTimeout;
const DEBOUNCE_DELAY = 300;

// Enhanced real-time validation with debouncing and comprehensive checks
function validateFieldRealTime(fieldType, value, relatedFields = {}) {
  clearTimeout(validationTimeout);
  validationTimeout = setTimeout(() => {
    const form = document.getElementById("schedule-form");
    if (!form) return;

    const formData = new FormData(form);
    const currentScheduleId = formData.get("schedule_id") || "";

    // Build comprehensive validation data
    const validationData = {
      action: "validate_partial",
      semester_id: window.currentSemester?.semester_id,
      department_id: window.departmentId,
      [fieldType]: value,
      schedule_id: currentScheduleId, // Important for update scenarios
      ...relatedFields,
    };

    // Add all relevant fields for comprehensive conflict checking
    const relevantFields = [
      "course_code",
      "section_name",
      "faculty_name",
      "room_name",
      "day_of_week",
      "start_time",
      "end_time",
    ];

    relevantFields.forEach((field) => {
      const fieldValue = formData.get(field)?.trim() || "";
      if (fieldValue) {
        validationData[field] = fieldValue;
      }
    });

    console.log(`Validating ${fieldType}:`, validationData);

    fetch("/chair/generate-schedules", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(validationData),
    })
      .then((response) => response.json())
      .then((result) => {
        console.log("Validation response:", result);
        handleValidationResponse(fieldType, result, value);
      })
      .catch((error) => {
        console.error("Real-time validation error:", error);
        displayConflictWarning(
          fieldType.replace("_", "-"),
          "🔧 Validation service temporarily unavailable",
          "warning"
        );
      });
  }, DEBOUNCE_DELAY);
}

// Enhanced validation response handler
function handleValidationResponse(fieldType, result, originalValue) {
  const fieldId = fieldType.replace("_", "-");

  // Clear previous warnings
  removeConflictWarning(fieldId);

  if (!result.success) {
    displayConflictWarning(
      fieldId,
      result.message || "Validation failed",
      "error"
    );
    return;
  }

  if (result.conflicts && result.conflicts.length > 0) {
    const conflictMessages = result.conflicts.map((conflict) => {
      // Format conflict messages for better readability
      if (conflict.includes("Section")) {
        return `👥 ${conflict}`;
      } else if (conflict.includes("Faculty")) {
        return `👨‍🏫 ${conflict}`;
      } else if (conflict.includes("Room")) {
        return `🏫 ${conflict}`;
      } else if (conflict.includes("time")) {
        return `⏰ ${conflict}`;
      }
      return `⚠️ ${conflict}`;
    });

    const uniqueMessages = [...new Set(conflictMessages)];
    const displayMessage = uniqueMessages.join("\n");

    displayConflictWarning(fieldId, displayMessage, "error");

    // Also highlight related fields if needed
    if (fieldType === "start_time" || fieldType === "end_time") {
      const otherTimeField =
        fieldType === "start_time" ? "end-time" : "start-time";
      displayConflictWarning(otherTimeField, "Time conflict detected", "error");
    }
  } else {
    // No conflicts - show success for important fields
    if (
      ["faculty_name", "room_name", "section_name"].includes(fieldType) &&
      originalValue
    ) {
      displayConflictWarning(fieldId, "✅ No conflicts detected", "success");
    }
  }
}

// Enhanced drag and drop that works for all schedule cards
function handleDragStart(e) {
  // Allow dragging from ANY schedule card, including continuation ones
  draggedElement = e.target.closest(".schedule-card");
  if (!draggedElement) return;

  e.dataTransfer.setData("text/plain", draggedElement.dataset.scheduleId);
  e.dataTransfer.effectAllowed = "move";
  draggedElement.classList.add("dragging");

  // Store original position data
  draggedElement.dataset.originalDay =
    draggedElement.closest(".schedule-cell").dataset.day;
  draggedElement.dataset.originalSlotStart =
    draggedElement.closest(".schedule-cell").dataset.slotStart;
  draggedElement.dataset.originalSlotEnd =
    draggedElement.closest(".schedule-cell").dataset.slotEnd;

  setTimeout(() => {
    draggedElement.style.opacity = "0.4";
  }, 0);
}

function handleDragEnd(e) {
  if (draggedElement) {
    draggedElement.style.opacity = "1";
    draggedElement.classList.remove("dragging");
  }
  draggedElement = null;
  document.querySelectorAll(".drop-zone.drag-over").forEach((zone) => {
    zone.classList.remove("drag-over");
  });
}

function handleDragEnter(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.target.classList.add("drag-over");
    e.preventDefault();
  }
}

function handleDragOver(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";
  }
}

function handleDragLeave(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.target.classList.remove("drag-over");
  }
}

// Enhanced calculateEndTime that handles any time format
function calculateEndTime(startTime, durationMinutes = 60) {
  // Handle various time formats
  let formattedStartTime = startTime;

  // If it's in HHMM format without colon, add colon
  if (!formattedStartTime.includes(":") && formattedStartTime.length >= 3) {
    const timeStr = formattedStartTime.padStart(4, "0");
    formattedStartTime =
      timeStr.substring(0, 2) + ":" + timeStr.substring(2, 4);
  }

  // Parse time
  const [hours, minutes] = formattedStartTime.split(":").map(Number);
  const startDate = new Date(2000, 0, 1, hours, minutes);
  const endDate = new Date(startDate.getTime() + durationMinutes * 60000);

  // Format back to HH:MM
  const endHours = endDate.getHours().toString().padStart(2, "0");
  const endMinutes = endDate.getMinutes().toString().padStart(2, "0");

  return `${endHours}:${endMinutes}`;
}

// Enhanced drop handler that understands schedule duration
function handleDrop(e) {
  e.preventDefault();
  const dropZone = e.target.closest(".drop-zone");
  if (!dropZone || !draggedElement) return;

  dropZone.classList.remove("drag-over");
  const scheduleId = e.dataTransfer.getData("text/plain");
  const newDay = dropZone.dataset.day;
  const newStartTime = dropZone.dataset.startTime; // This is the slot start time

  console.log(
    "Dropping schedule:",
    scheduleId,
    "to",
    newDay,
    "at slot",
    newStartTime
  );

  const scheduleIndex = window.scheduleData.findIndex(
    (s) => s.schedule_id == scheduleId
  );

  if (scheduleIndex !== -1) {
    const originalSchedule = window.scheduleData[scheduleIndex];

    // Calculate new times based on the original duration
    const originalStart = new Date(`2000-01-01T${originalSchedule.start_time}`);
    const originalEnd = new Date(`2000-01-01T${originalSchedule.end_time}`);
    const durationMinutes = (originalEnd - originalStart) / (1000 * 60);

    // Use the slot start time as the new start time
    const newStart = new Date(`2000-01-01T${newStartTime}:00`);
    const newEnd = new Date(newStart.getTime() + durationMinutes * 60000);

    const formattedStartTime = newStart.toTimeString().substring(0, 8);
    const formattedEndTime = newEnd.toTimeString().substring(0, 8);

    console.log("Time update details:", {
      original: `${originalSchedule.start_time} - ${originalSchedule.end_time}`,
      new: `${formattedStartTime} - ${formattedEndTime}`,
      duration: durationMinutes + " minutes",
    });

    // Update the schedule
    window.scheduleData[scheduleIndex].day_of_week = newDay;
    window.scheduleData[scheduleIndex].start_time = formattedStartTime;
    window.scheduleData[scheduleIndex].end_time = formattedEndTime;

    // Refresh display
    safeUpdateScheduleDisplay(window.scheduleData);
    showNotification(
      `Schedule moved to ${newDay} ${formattedStartTime.substring(
        0,
        5
      )}-${formattedEndTime.substring(0, 5)}`,
      "success"
    );
  }
}

// Make sure ALL schedule cards are draggable and editable
function initializeDragAndDrop() {
  const dropZones = document.querySelectorAll(".drop-zone");
  const draggables = document.querySelectorAll(".schedule-card.draggable");

  console.log("Initializing drag and drop:", {
    dropZones: dropZones.length,
    draggables: draggables.length,
  });

  dropZones.forEach((zone) => {
    zone.removeEventListener("dragover", handleDragOver);
    zone.removeEventListener("dragenter", handleDragEnter);
    zone.removeEventListener("dragleave", handleDragLeave);
    zone.removeEventListener("drop", handleDrop);

    zone.addEventListener("dragover", handleDragOver);
    zone.addEventListener("dragenter", handleDragEnter);
    zone.addEventListener("dragleave", handleDragLeave);
    zone.addEventListener("drop", handleDrop);
  });

  draggables.forEach((draggable) => {
    draggable.removeEventListener("dragstart", handleDragStart);
    draggable.removeEventListener("dragend", handleDragEnd);

    draggable.addEventListener("dragstart", handleDragStart);
    draggable.addEventListener("dragend", handleDragEnd);

    // Ensure edit buttons work
    const editBtn = draggable.querySelector(".edit-schedule-btn");
    const deleteBtn = draggable.querySelector(".delete-schedule-btn");

    if (editBtn) {
      editBtn.onclick = (e) => {
        e.stopPropagation();
        const scheduleId = draggable.dataset.scheduleId;
        editScheduleFromAnyCell(scheduleId);
      };
    }

    if (deleteBtn) {
      deleteBtn.onclick = (e) => {
        e.stopPropagation();
        const scheduleId = draggable.dataset.scheduleId;
        const schedule = window.scheduleData.find(
          (s) => s.schedule_id == scheduleId
        );
        if (schedule) {
          openDeleteSingleModal(
            scheduleId,
            schedule.course_code,
            schedule.section_name,
            schedule.day_of_week,
            formatTime(schedule.start_time.substring(0, 5)),
            formatTime(schedule.end_time.substring(0, 5))
          );
        }
      };
    }
  });
}

function openDeleteAllModal() {
  console.log("Opening delete all modal...");
  const modal = document.getElementById("delete-all-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    console.log("Delete all modal opened successfully");
  } else {
    console.error("Delete all modal element not found!");
    if (
      confirm(
        "Are you sure you want to delete all schedules? This action cannot be undone."
      )
    ) {
      confirmDeleteAllSchedules();
    }
  }
}

function closeDeleteAllModal() {
  console.log("Closing delete all modal...");
  const modal = document.getElementById("delete-all-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

function confirmDeleteAllSchedules() {
  console.log("Confirming deletion of all schedules...");

  const deleteButton = document.querySelector(
    '#delete-confirmation-modal button[onclick="confirmDeleteAllSchedules()"]'
  );
  const originalText = deleteButton ? deleteButton.innerHTML : "";

  if (deleteButton) {
    deleteButton.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteButton.disabled = true;
  }

  // Debug: Log what we're sending
  const requestData = {
    action: "delete_schedules",
    confirm: "true",
    semester_id: window.currentSemester?.semester_id || "",
    department_id: window.departmentId || "",
  };

  console.log("Sending delete request:", requestData);

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(requestData),
  })
    .then((response) => {
      console.log("Delete all response status:", response.status);
      console.log("Delete all response headers:", response.headers);
      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);
      return response.json();
    })
    .then((data) => {
      console.log("Delete all response data:", data);

      if (data.success) {
        showNotification(
          "All schedules deleted successfully! Deleted " +
            (data.deleted_count || 0) +
            " schedules.",
          "success"
        );

        // Clear frontend data
        window.scheduleData = [];

        // Force refresh all views
        safeUpdateScheduleDisplay([]);
        buildCurrentSemesterCourseMappings();

        // Hide generation results
        const generationResults = document.getElementById("generation-results");
        if (generationResults) generationResults.classList.add("hidden");

        // Force a page reload after successful deletion to ensure clean state
        setTimeout(() => {
          console.log("Reloading page to ensure clean state...");
          location.reload();
        }, 1000);
      } else {
        showNotification(
          "Error: " + (data.message || "Failed to delete all schedules"),
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Delete all error:", error);
      showNotification(
        "Error deleting all schedules: " + error.message,
        "error"
      );
    })
    .finally(() => {
      if (deleteButton) {
        deleteButton.innerHTML = originalText;
        deleteButton.disabled = false;
      }
      closeDeleteModal();
      closeDeleteAllModal();
    });
}

let currentDeleteScheduleId = null;

function openDeleteSingleModal(
  scheduleId,
  courseCode,
  sectionName,
  day,
  startTime,
  endTime
) {
  console.log("Opening single delete modal for schedule:", scheduleId);
  currentDeleteScheduleId = scheduleId;

  const detailsElement = document.getElementById("single-delete-details");
  if (detailsElement) {
    detailsElement.innerHTML =
      '<div class="text-sm">' +
      '<div class="font-semibold">' +
      courseCode +
      " - " +
      sectionName +
      "</div>" +
      '<div class="text-gray-600 mt-1">' +
      day +
      " • " +
      startTime +
      " to " +
      endTime +
      "</div>" +
      "</div>";
  }

  const modal = document.getElementById("delete-single-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    console.log("Single delete modal opened successfully");
  } else {
    console.error("Single delete modal element not found!");
    if (
      confirm(
        "Are you sure you want to delete " +
          courseCode +
          " - " +
          sectionName +
          "?"
      )
    ) {
      confirmDeleteSingleSchedule();
    }
  }
}

function closeDeleteSingleModal() {
  console.log("Closing single delete modal...");
  const modal = document.getElementById("delete-single-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
  currentDeleteScheduleId = null;
}

function confirmDeleteSingleSchedule() {
  if (!currentDeleteScheduleId) {
    console.error("No schedule ID set for deletion");
    showNotification("Error: No schedule selected for deletion", "error");
    return;
  }

  console.log("Confirming deletion of schedule:", currentDeleteScheduleId);
  const deleteButton = document.querySelector(
    '#delete-single-modal button[onclick="confirmDeleteSingleSchedule()"]'
  );
  const originalText = deleteButton ? deleteButton.innerHTML : "";

  if (deleteButton) {
    deleteButton.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteButton.disabled = true;
  }

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "delete_schedule",
      schedule_id: currentDeleteScheduleId,
    }),
  })
    .then((response) => {
      console.log("Single delete response status:", response.status);
      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);
      return response.json();
    })
    .then((data) => {
      console.log("Single delete response data:", data);
      if (data.success) {
        showNotification("Schedule deleted successfully!", "success");
        window.scheduleData = window.scheduleData.filter(
          (s) => s.schedule_id != currentDeleteScheduleId
        );
        safeUpdateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();
        buildCurrentSemesterCourseMappings();
      } else {
        showNotification(data.message || "Failed to delete schedule.", "error");
      }
    })
    .catch((error) => {
      console.error("Single delete error:", error);
      showNotification("Error deleting schedule: " + error.message, "error");
    })
    .finally(() => {
      if (deleteButton) {
        deleteButton.innerHTML = originalText;
        deleteButton.disabled = false;
      }
      closeDeleteSingleModal();
    });
}

function deleteSchedule(scheduleId) {
  const schedule = window.scheduleData.find((s) => s.schedule_id == scheduleId);
  if (schedule) {
    openDeleteSingleModal(
      scheduleId,
      schedule.course_code,
      schedule.section_name,
      schedule.day_of_week,
      formatTime(schedule.start_time.substring(0, 5)),
      formatTime(schedule.end_time.substring(0, 5))
    );
  } else {
    console.error("Schedule not found for deletion:", scheduleId);
    showNotification("Error: Schedule not found", "error");
  }
}

// Enhanced time formatting that handles any time input
function formatTime(timeString) {
  if (!timeString) return "";

  // Handle various time formats
  let formattedTime = timeString;
  if (!formattedTime.includes(":")) {
    // If it's just numbers like "730", convert to "07:30"
    const timeStr = formattedTime.padStart(4, "0");
    formattedTime = timeStr.substring(0, 2) + ":" + timeStr.substring(2, 4);
  }

  const [hours, minutes] = formattedTime.split(":");
  const date = new Date(2000, 0, 1, hours, minutes);
  return date.toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function showNotification(message, type = "success", duration = 5000) {
  const existingNotification = document.getElementById("notification");
  if (existingNotification) existingNotification.remove();

  const notificationDiv = document.createElement("div");
  notificationDiv.id = "notification";

  let notificationClass = "fixed top-4 right-4 z-50 max-w-sm w-full ";
  let iconClass = "";
  let textClass = "";
  let buttonClass = "";

  if (type === "success") {
    notificationClass += "bg-green-50 border border-green-200";
    iconClass = "fa-check-circle text-green-400";
    textClass = "text-green-800";
    buttonClass = "text-green-400 hover:text-green-600";
  } else if (type === "error") {
    notificationClass += "bg-red-50 border border-red-200";
    iconClass = "fa-exclamation-circle text-red-400";
    textClass = "text-red-800";
    buttonClass = "text-red-400 hover:text-red-600";
  } else {
    notificationClass += "bg-yellow-50 border border-yellow-200";
    iconClass = "fa-exclamation-triangle text-yellow-400";
    textClass = "text-yellow-800";
    buttonClass = "text-yellow-400 hover:text-yellow-600";
  }

  notificationClass +=
    " rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full";
  notificationDiv.className = notificationClass;

  notificationDiv.innerHTML =
    '<div class="flex p-4">' +
    '<div class="flex-shrink-0">' +
    '<i class="fas ' +
    iconClass +
    ' text-lg"></i>' +
    "</div>" +
    '<div class="ml-3 flex-1">' +
    '<p class="text-sm font-medium ' +
    textClass +
    ' whitespace-pre-line">' +
    message +
    "</p>" +
    "</div>" +
    '<div class="ml-auto pl-3">' +
    '<button class="inline-flex ' +
    buttonClass +
    '" onclick="this.parentElement.parentElement.parentElement.remove()">' +
    '<i class="fas fa-times"></i>' +
    "</button>" +
    "</div>" +
    "</div>";

  document.body.appendChild(notificationDiv);

  setTimeout(() => {
    notificationDiv.classList.remove("translate-x-full");
    notificationDiv.classList.add("translate-x-0");
  }, 100);

  setTimeout(() => {
    if (notificationDiv.parentElement) {
      notificationDiv.classList.add("translate-x-full");
      setTimeout(() => {
        if (notificationDiv.parentElement) notificationDiv.remove();
      }, 300);
    }
  }, duration);
}

// Enhanced autoFillCourseName with better messaging
// Enhanced autoFillCourseName with time calculation based on course units
function autoFillCourseName(courseCode) {
  const courseNameInput = document.getElementById("course-name");
  if (!courseCode || !courseNameInput) return;

  const enteredCode = courseCode.trim().toUpperCase();
  console.log("Looking up course:", enteredCode);

  // Clear previous warnings
  removeConflictWarning("course-code");
  removeConflictWarning("course-name");

  // Check for conflicts
  const conflict = validateCourseConflict(enteredCode, "");

  if (currentSemesterCourses[enteredCode]) {
    const course = currentSemesterCourses[enteredCode];
    courseNameInput.value = course.name;
    console.log("Found course:", course);

    // Auto-calculate end time based on course units
    if (course.units) {
      const durationMinutes = course.units * 60; // 1 unit = 1 hour
      setTimeout(() => {
        calculateAutoEndTime();
        showNotification(
          `Course detected: ${course.units} unit${
            course.units !== 1 ? "s" : ""
          } (${durationMinutes} minutes)`,
          "info",
          3000
        );
      }, 100);
    }

    // Show success message for valid course
    if (!conflict) {
      displayConflictWarning(
        "course-code",
        "✅ Course found in curriculum! Course name auto-filled.",
        "success"
      );
    }

    setTimeout(() => {
      filterSectionsByYearLevel();
      handleSectionChange();
    }, 100);
  } else {
    console.log("Course not found in current semester");
    courseNameInput.value = "";
    resetSectionFilter();

    if (!conflict && enteredCode) {
      displayConflictWarning(
        "course-code",
        "🔍 Course not found in current semester curriculum. Please verify the course code.",
        "warning"
      );
    }
  }
}

function filterSectionsByYearLevel() {
  const sectionSelect = document.getElementById("section-name");
  const courseCodeInput = document.getElementById("course-code");
  if (!sectionSelect || !courseCodeInput) return;

  const courseCode = courseCodeInput.value.trim().toUpperCase();
  if (!courseCode) {
    resetSectionFilter();
    return;
  }

  const course = currentSemesterCourses[courseCode];
  if (!course || !course.year_level) {
    resetSectionFilter();
    highlightConflictField(
      "course-code",
      "No year level information found for course " +
        courseCode +
        ". Showing all sections."
    );
    return;
  }

  const targetYearLevel = course.year_level;
  console.log(
    "Filtering sections for year level:",
    targetYearLevel,
    "from course:",
    courseCode
  );

  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach((option) => (option.style.display = ""));

  const searchYear = targetYearLevel.toLowerCase().replace(" year", "").trim();
  let foundMatchingSections = false;

  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach((optgroup) => {
    const groupLabel = optgroup.label.toLowerCase();
    const groupYearLevel = groupLabel.replace(" year", "").trim();

    if (groupYearLevel === searchYear) {
      const options = optgroup.querySelectorAll("option");
      options.forEach((option) => (option.style.display = ""));
      optgroup.style.display = "";
      foundMatchingSections = true;
    } else {
      const options = optgroup.querySelectorAll("option");
      options.forEach((option) => (option.style.display = "none"));
      optgroup.style.display = "none";
    }
  });

  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
  if (selectedOption && selectedOption.style.display === "none") {
    sectionSelect.value = "";
  }

  if (foundMatchingSections) {
    resetConflictStyles();
  } else {
    console.warn("No sections found for year level:", targetYearLevel);
    highlightConflictField(
      "section-name",
      "No " +
        targetYearLevel +
        " sections found for " +
        courseCode +
        ". Please check section availability."
    );
    resetSectionFilter();
  }
}

function resetSectionFilter() {
  const sectionSelect = document.getElementById("section-name");
  if (!sectionSelect) return;

  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach((option) => (option.style.display = ""));

  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach((optgroup) => (optgroup.style.display = ""));
}

// Enhanced updateTimeFields for flexible time inputs
function updateTimeFields() {
  const startTimeInput = document.getElementById("start-time");
  const endTimeInput = document.getElementById("end-time");
  const modalStartTime = document.getElementById("modal-start-time");
  const modalEndTime = document.getElementById("modal-end-time");

  if (startTimeInput && modalStartTime) {
    // Ensure time format includes seconds
    let startTime = startTimeInput.value;
    if (startTime && !startTime.includes(":")) {
      // Convert HHMM to HH:MM
      const timeStr = startTime.padStart(4, "0");
      startTime = timeStr.substring(0, 2) + ":" + timeStr.substring(2, 4);
    }
    modalStartTime.value = startTime ? startTime + ":00" : "";
  }

  if (endTimeInput && modalEndTime) {
    let endTime = endTimeInput.value;
    if (endTime && !endTime.includes(":")) {
      const timeStr = endTime.padStart(4, "0");
      endTime = timeStr.substring(0, 2) + ":" + timeStr.substring(2, 4);
    }
    modalEndTime.value = endTime ? endTime + ":00" : "";
  }
}

function updateDayField() {
  const daySelect = document.getElementById("day-select");
  const modalDay = document.getElementById("modal-day");
  if (daySelect && modalDay) modalDay.value = daySelect.value;
}

// Enhanced form submission with time validation
function handleScheduleSubmit(e) {
  e.preventDefault();
  console.log("Submitting schedule form with enhanced validation...");

  resetConflictStyles();

  // Validate time formats
  const startTimeInput = document.getElementById("start-time");
  const endTimeInput = document.getElementById("end-time");

  if (!validateTimeInput(startTimeInput) || !validateTimeInput(endTimeInput)) {
    return;
  }

  const currentSemesterId = window.currentSemester?.semester_id;
  if (!currentSemesterId) {
    showNotification("No active semester selected", "error");
    return;
  }

  const formData = new FormData(document.getElementById("schedule-form"));
  const data = {
    action: currentEditingId ? "update_schedule" : "add_schedule",
    schedule_id: formData.get("schedule_id"),
    course_code: formData.get("course_code").trim(),
    course_name: formData.get("course_name").trim(),
    section_name: formData.get("section_name").trim(),
    faculty_name: formData.get("faculty_name").trim(),
    room_name: formData.get("room_name").trim() || "Online",
    day_of_week: formData.get("day_of_week"),
    start_time: formData.get("start_time"),
    end_time: formData.get("end_time"),
    schedule_type: formData.get("schedule_type") || "f2f",
    semester_id: currentSemesterId,
    department_id: window.departmentId,
  };

  console.log("Enhanced form data:", data);

  // Validate required fields
  const requiredFields = [
    { field: "course_code", name: "Course Code" },
    { field: "course_name", name: "Course Name" },
    { field: "section_name", name: "Section" },
    { field: "faculty_name", name: "Faculty" },
    { field: "day_of_week", name: "Day Pattern" },
  ];

  let hasEmptyFields = false;
  requiredFields.forEach(({ field, name }) => {
    if (!data[field]) {
      highlightConflictField(field.replace("_", "-"), `${name} is required`);
      hasEmptyFields = true;
    }
  });

  if (hasEmptyFields) {
    showNotification("Please fill out all required fields.", "error");
    return;
  }

  // Enhanced time validation
  const startTime = data.start_time.substring(0, 5);
  const endTime = data.end_time.substring(0, 5);

  if (startTime >= endTime) {
    highlightConflictField("start-time", "Start time must be before end time");
    highlightConflictField("end-time", "End time must be after start time");
    showNotification("End time must be after start time.", "error");
    return;
  }

  // Check minimum class duration (at least 30 minutes)
  const start = new Date(`2000-01-01T${startTime}:00`);
  const end = new Date(`2000-01-01T${endTime}:00`);
  const durationMinutes = (end - start) / (1000 * 60);

  if (durationMinutes < 30) {
    highlightConflictField("end-time", "Minimum class duration is 30 minutes");
    showNotification("Class duration must be at least 30 minutes.", "error");
    return;
  }

  // Check for conflicts before submission
  performFinalConflictCheck(data)
    .then((hasConflicts) => {
      if (hasConflicts) {
        showNotification(
          "Please resolve all conflicts before submitting.",
          "error"
        );
        return;
      }
      submitScheduleData(data);
    })
    .catch((error) => {
      console.error("Final conflict check error:", error);
      showNotification("Error checking conflicts: " + error.message, "error");
    });
}

// Perform final comprehensive conflict check
function performFinalConflictCheck(data) {
  return new Promise((resolve) => {
    const checkData = {
      action: "validate_complete",
      semester_id: data.semester_id,
      department_id: data.department_id,
      schedule_id: data.schedule_id || "",
      course_code: data.course_code,
      section_name: data.section_name,
      faculty_name: data.faculty_name,
      room_name: data.room_name,
      day_of_week: data.day_of_week,
      start_time: data.start_time,
      end_time: data.end_time,
    };

    fetch("/chair/generate-schedules", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(checkData),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.success && result.conflicts && result.conflicts.length > 0) {
          // Display all conflicts
          result.conflicts.forEach((conflict) => {
            if (conflict.includes("Section")) {
              highlightConflictField("section-name", conflict);
            } else if (conflict.includes("Faculty")) {
              highlightConflictField("faculty-name", conflict);
            } else if (conflict.includes("Room")) {
              highlightConflictField("room-name", conflict);
            } else if (conflict.includes("time")) {
              highlightConflictField("start-time", conflict);
              highlightConflictField("end-time", conflict);
            }
          });
          resolve(true);
        } else {
          resolve(false);
        }
      })
      .catch(() => resolve(false));
  });
}

// Submit schedule data after validation
function submitScheduleData(data) {
  const submitButton = document.querySelector(
    '#schedule-form button[type="submit"]'
  );
  const originalText = submitButton.innerHTML;
  submitButton.innerHTML =
    '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
  submitButton.disabled = true;

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data),
  })
    .then((response) => response.json())
    .then((result) => {
      console.log("Save response:", result);
      handleSaveResponse(result, data);
    })
    .catch((error) => {
      console.error("Error saving schedule:", error);
      showNotification("Error saving schedule: " + error.message, "error");
    })
    .finally(() => {
      submitButton.innerHTML = originalText;
      submitButton.disabled = false;
    });
}

// Handle save response
function handleSaveResponse(result, originalData) {
  if (result.success) {
    closeModal();
    resetConflictStyles();

    let message =
      result.message ||
      (currentEditingId
        ? "Schedule updated successfully!"
        : "Schedule added successfully!");

    if (result.schedules && result.schedules.length > 1) {
      message =
        "Schedules added successfully for " +
        result.schedules.length +
        " days!";
      result.schedules.forEach((schedule) => {
        window.scheduleData.push({
          ...schedule,
          semester_id: originalData.semester_id,
        });
      });
    } else if (result.schedules && result.schedules.length === 1) {
      if (currentEditingId) {
        const index = window.scheduleData.findIndex(
          (s) => s.schedule_id == currentEditingId
        );
        if (index !== -1)
          window.scheduleData[index] = {
            ...window.scheduleData[index],
            ...result.schedules[0],
            semester_id: originalData.semester_id,
          };
      } else {
        window.scheduleData.push({
          ...result.schedules[0],
          semester_id: originalData.semester_id,
        });
      }
    }

    if (result.partial_success) {
      message += " (" + result.failed_days + " day(s) had conflicts)";
    }

    showNotification(message, "success");
    safeUpdateScheduleDisplay(window.scheduleData);
    initializeDragAndDrop();
    buildCurrentSemesterCourseMappings();
  } else {
    resetConflictStyles();
    if (result.conflicts && result.conflicts.length > 0) {
      const conflictDetails = result.conflicts
        .map((conflict) => `• ${conflict}`)
        .join("\n");
      showNotification(
        "Schedule conflicts detected:\n" + conflictDetails,
        "error",
        10000
      );

      // Highlight conflicting fields
      result.conflicts.forEach((conflict) => {
        if (conflict.includes("faculty"))
          highlightConflictField("faculty-name", conflict);
        if (conflict.includes("room"))
          highlightConflictField("room-name", conflict);
        if (conflict.includes("section"))
          highlightConflictField("section-name", conflict);
        if (conflict.includes("time")) {
          highlightConflictField("start-time", conflict);
          highlightConflictField("end-time", conflict);
        }
      });
    } else {
      showNotification(result.message || "Failed to save schedule.", "error");
    }
  }
}

function highlightConflictField(fieldId, message) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.classList.add("border-red-500", "bg-red-50");
    field.classList.remove("border-gray-300", "bg-white");

    let tooltip = field.parentNode.querySelector(".conflict-tooltip");
    if (!tooltip) {
      tooltip = document.createElement("div");
      tooltip.className = "conflict-tooltip text-red-600 text-xs mt-1";
      field.parentNode.appendChild(tooltip);
    }
    tooltip.textContent = message;
  }
}

// Enhanced reset function
function resetConflictStyles() {
  const form = document.getElementById("schedule-form");
  if (!form) return;

  const fields = form.querySelectorAll("input, select, textarea");
  fields.forEach((field) => {
    const fieldId = field.id;
    removeConflictWarning(fieldId);
  });
}

function buildCurrentSemesterCourseMappings() {
  currentSemesterCourses = {};
  console.log(
    "Building course mappings for current semester:",
    window.currentSemester
  );
  console.log("Available curriculum courses:", window.curriculumCourses);

  if (window.curriculumCourses && window.curriculumCourses.length > 0) {
    console.log("Using curriculum courses:", window.curriculumCourses.length);
    window.curriculumCourses.forEach((course) => {
      if (course.course_code && course.course_name) {
        currentSemesterCourses[course.course_code.trim().toUpperCase()] = {
          code: course.course_code,
          name: course.course_name,
          course_id: course.course_id,
          year_level: course.curriculum_year,
          semester: course.curriculum_semester,
          units: course.units,
          lecture_hours: course.lecture_hours,
          lab_hours: course.lab_hours,
        };
      }
    });

    console.log(
      "Course mappings built:",
      Object.keys(currentSemesterCourses).length,
      "unique courses"
    );
    console.log(
      "Sample courses:",
      Object.values(currentSemesterCourses).slice(0, 3)
    );
    updateCourseCodesDatalist();
  } else {
    console.warn(
      "No curriculum courses found! Check if curriculum is set up correctly."
    );
    const currentSemesterId = window.currentSemester?.semester_id;
    if (currentSemesterId) {
      const currentSemesterSchedules = window.scheduleData.filter(
        (schedule) => schedule.semester_id == currentSemesterId
      );
      console.log(
        "Fallback: Using",
        currentSemesterSchedules.length,
        "schedules for current semester"
      );

      currentSemesterSchedules.forEach((schedule) => {
        if (schedule.course_code && schedule.course_name) {
          currentSemesterCourses[schedule.course_code.trim().toUpperCase()] = {
            code: schedule.course_code,
            name: schedule.course_name,
            course_id: schedule.course_id,
          };
        }
      });
      updateCourseCodesDatalist();
    }
  }
}

function updateCourseCodesDatalist() {
  const courseCodesDatalist = document.getElementById("course-codes");
  if (!courseCodesDatalist) return;

  courseCodesDatalist.innerHTML = "";
  Object.values(currentSemesterCourses).forEach((course) => {
    const option = document.createElement("option");
    option.value = course.code;
    option.setAttribute("data-name", course.name);
    option.setAttribute("data-year-level", course.year_level || "");
    option.setAttribute("data-course-id", course.course_id || "");
    courseCodesDatalist.appendChild(option);
  });

  console.log(
    "Updated course codes datalist with",
    courseCodesDatalist.children.length,
    "options"
  );
}

// Enhanced syncCourseName with conflict detection
function syncCourseName() {
  const courseCodeInput = document.getElementById("course-code");
  const courseNameInput = document.getElementById("course-name");
  if (!courseCodeInput || !courseNameInput) return;

  const enteredCode = courseCodeInput.value.trim().toUpperCase();
  const enteredName = courseNameInput.value.trim();

  console.log("Looking up course:", enteredCode);

  // Check for conflicts
  const conflict = validateCourseConflict(enteredCode, enteredName);
  if (conflict) {
    highlightConflictField("course-code", conflict.message);
    if (enteredName) {
      highlightConflictField("course-name", conflict.message);
    }
  } else {
    resetConflictField("course-code");
    resetConflictField("course-name");
  }

  if (currentSemesterCourses[enteredCode]) {
    const course = currentSemesterCourses[enteredCode];
    courseNameInput.value = course.name;
    console.log("Found course:", course);
  } else {
    console.log("Course not found in current semester");
  }
}

// Enhanced syncCourseCode with conflict detection
function syncCourseCode() {
  const courseCodeInput = document.getElementById("course-code");
  const courseNameInput = document.getElementById("course-name");
  if (!courseCodeInput || !courseNameInput) return;

  const enteredName = courseNameInput.value.trim().toLowerCase();
  const matchingCourse = Object.values(currentSemesterCourses).find(
    (course) => course.name.toLowerCase() === enteredName
  );

  if (matchingCourse) {
    courseCodeInput.value = matchingCourse.code;
    console.log("Found matching course code:", matchingCourse.code);

    // Check for conflicts after syncing
    const conflict = validateCourseConflict(matchingCourse.code, enteredName);
    if (conflict) {
      highlightConflictField("course-code", conflict.message);
      highlightConflictField("course-name", conflict.message);
    } else {
      resetConflictField("course-code");
      resetConflictField("course-name");
    }
  }
}

// Enhanced section change handler with better messages
function handleSectionChange() {
  const sectionSelect = document.getElementById("section-name");
  const courseCodeInput = document.getElementById("course-code");
  const roomSelect = document.getElementById("room-name");
  if (!sectionSelect) return;

  const selectedSection = sectionSelect.value;
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
  console.log("Section changed to:", selectedSection);

  // Clear previous section warnings
  removeConflictWarning("section-name");

  if (selectedOption && roomSelect) {
    const sectionCapacity = extractCapacityFromSection(selectedOption.text);
    if (sectionCapacity) {
      highlightSuitableRooms(sectionCapacity);
      displayConflictWarning(
        "room-name",
        `📊 Room highlighting adjusted for section capacity: ${sectionCapacity} students`,
        "info"
      );
    }
  }

  if (courseCodeInput && courseCodeInput.value) {
    validateCourseSectionCompatibility(courseCodeInput.value, selectedSection);
  }

  if (selectedSection) {
    checkExistingSectionSchedules(selectedSection);
  }

  updateSectionDetails(selectedOption);
}

function extractCapacityFromSection(sectionText) {
  const match = sectionText.match(/\((\d+)\/(\d+)\)/);
  return match ? parseInt(match[2]) : null;
}

function highlightSuitableRooms(requiredCapacity) {
  const roomSelect = document.getElementById("room-name");
  if (!roomSelect) return;

  const options = roomSelect.querySelectorAll("option");
  options.forEach((option) => {
    if (option.value === "Online") {
      option.style.backgroundColor = "#f0f9ff";
      return;
    }

    const roomCapacity = extractRoomCapacity(option.text);
    if (roomCapacity && roomCapacity >= requiredCapacity) {
      option.style.backgroundColor = "#f0fff4";
      option.title =
        "Capacity: " +
        roomCapacity +
        " (Meets requirement: " +
        requiredCapacity +
        ")";
    } else if (roomCapacity) {
      option.style.backgroundColor = "#fff0f0";
      option.title =
        "Capacity: " + roomCapacity + " (Required: " + requiredCapacity + ")";
    } else {
      option.style.backgroundColor = "";
      option.title = "";
    }
  });

  console.log("Highlighted rooms for capacity requirement:", requiredCapacity);
}

function extractRoomCapacity(roomText) {
  const match = roomText.match(/\((\d+)\)/);
  return match ? parseInt(match[1]) : null;
}

// Enhanced section compatibility check
function validateCourseSectionCompatibility(courseCode, sectionName) {
  const course = currentSemesterCourses[courseCode.toUpperCase()];
  if (!course || !course.year_level) return;

  const sectionSelect = document.getElementById("section-name");
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
  if (!selectedOption) return;

  const sectionYearLevel = selectedOption.getAttribute("data-year-level");
  if (sectionYearLevel && course.year_level !== sectionYearLevel) {
    console.warn(
      "Course-Section Mismatch: Course (" +
        course.year_level +
        ") ≠ Section (" +
        sectionYearLevel +
        ")"
    );
    displayConflictWarning(
      "section-name",
      `📚 Year level mismatch: Course "${courseCode}" is for ${course.year_level} but section "${sectionName}" is ${sectionYearLevel}. This may not be appropriate.`,
      "warning"
    );
  } else {
    console.log("Course-section compatibility: OK");
    removeConflictWarning("section-name");
  }
}

// Enhanced existing section schedule check
function checkExistingSectionSchedules(sectionName) {
  const currentSemesterId = window.currentSemester?.semester_id;
  if (!currentSemesterId) return;

  const existingSchedules = window.scheduleData.filter(
    (schedule) =>
      schedule.section_name === sectionName &&
      schedule.semester_id == currentSemesterId
  );

  if (existingSchedules.length > 0) {
    console.log(
      "Section " +
        sectionName +
        " has " +
        existingSchedules.length +
        " existing schedules:",
      existingSchedules
    );
    const scheduleCount = existingSchedules.length;
    const courseCodes = [
      ...new Set(existingSchedules.map((s) => s.course_code)),
    ].join(", ");

    displayConflictWarning(
      "section-name",
      `📅 Section "${sectionName}" already has ${scheduleCount} scheduled course(s): ${courseCodes}. Adding more schedules may affect student workload.`,
      "info"
    );
  } else {
    removeConflictWarning("section-name");
  }
}

function updateSectionDetails(selectedOption) {
  let detailsDiv = document.getElementById("section-details");
  if (!detailsDiv) {
    detailsDiv = document.createElement("div");
    detailsDiv.id = "section-details";
    detailsDiv.className = "mt-2 p-2 bg-gray-50 rounded text-sm";
    const sectionSelect = document.getElementById("section-name");
    sectionSelect.parentNode.appendChild(detailsDiv);
  }

  if (selectedOption && selectedOption.value) {
    const sectionText = selectedOption.text;
    const yearLevel = selectedOption.getAttribute("data-year-level");
    detailsDiv.innerHTML =
      '<div class="flex justify-between items-center">' +
      '<span class="font-medium">Section Details:</span>' +
      '<span class="text-blue-600">' +
      (yearLevel || "Unknown Year") +
      "</span>" +
      "</div>" +
      '<div class="text-gray-600 mt-1">' +
      sectionText +
      "</div>";
    detailsDiv.style.display = "block";
  } else {
    detailsDiv.style.display = "none";
  }
}

function openAddModal() {
  console.log(
    "Opening add modal for current semester:",
    window.currentSemester
  );
  buildCurrentSemesterCourseMappings();

  const form = document.getElementById("schedule-form");
  if (form) form.reset();

  // Reset conflict styles specifically for course fields
  resetConflictField("course-code");
  resetConflictField("course-name");

  document.getElementById("modal-title").textContent = "Add Schedule";
  document.getElementById("schedule-id").value = "";
  document.getElementById("modal-day").value = "Monday";
  document.getElementById("modal-start-time").value = "07:30";
  document.getElementById("modal-end-time").value = "08:30";
  document.getElementById("course-code").value = "";
  document.getElementById("course-name").value = "";
  document.getElementById("faculty-name").value = "";
  document.getElementById("room-name").value = "Online";
  document.getElementById("section-name").value = "";

  document.getElementById("day-select").value = "Monday";
  document.getElementById("start-time").value = "07:30";
  document.getElementById("end-time").value = "08:30";
  document.getElementById("schedule-type").value = "f2f";

  resetSectionFilter();

  const sectionDetails = document.getElementById("section-details");
  if (sectionDetails) sectionDetails.style.display = "none";

  currentEditingId = null;
  showModal();
}

function openAddModalForSlot(day, startTime, endTime) {
  console.log("Opening add modal for slot:", day, startTime, endTime);
  openAddModal();

  document.getElementById("modal-day").value = day;
  document.getElementById("modal-start-time").value = startTime;
  document.getElementById("modal-end-time").value = endTime;

  document.getElementById("day-select").value = day;
  document.getElementById("start-time").value = startTime;
  document.getElementById("end-time").value = endTime;
}

function editSchedule(scheduleId) {
  console.log("✏️ Editing schedule:", scheduleId);
  const schedule = window.scheduleData.find((s) => s.schedule_id == scheduleId);
  if (!schedule) {
    console.error("Schedule not found:", scheduleId);
    showNotification("Schedule not found", "error");
    return;
  }

  resetConflictField("course-code");
  resetConflictField("course-name");

  console.log("Found schedule:", schedule);

  if (schedule.semester_id != window.currentSemester?.semester_id) {
    showNotification("Can only edit schedules from current semester", "error");
    return;
  }

  buildCurrentSemesterCourseMappings();
  document.getElementById("modal-title").textContent = "Edit Schedule";
  document.getElementById("schedule-id").value = schedule.schedule_id;
  document.getElementById("course-code").value = schedule.course_code || "";
  document.getElementById("course-name").value = schedule.course_name || "";
  document.getElementById("faculty-name").value = schedule.faculty_name || "";
  document.getElementById("room-name").value = schedule.room_name || "";
  document.getElementById("section-name").value = schedule.section_name || "";

  const day = schedule.day_of_week || "Monday";
  document.getElementById("modal-day").value = day;
  document.getElementById("day-select").value = day;

  const startTime = schedule.start_time
    ? schedule.start_time.substring(0, 5)
    : "07:30";
  const endTime = schedule.end_time
    ? schedule.end_time.substring(0, 5)
    : "08:30";
  document.getElementById("modal-start-time").value = startTime + ":00";
  document.getElementById("modal-end-time").value = endTime + ":00";
  document.getElementById("start-time").value = startTime;
  document.getElementById("end-time").value = endTime;

  currentEditingId = scheduleId;
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

function saveAllChanges() {
  const currentSemesterId = window.currentSemester?.semester_id;
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
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ schedules: updatedSchedules }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("All changes saved successfully!", "success");
        window.scheduleData = data.schedules || [];
        safeUpdateScheduleDisplay(window.scheduleData);
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

// Enhanced filter function for manual tab
function filterSchedulesManual() {
  const yearLevel = document.getElementById("filter-year-manual").value;
  const section = document.getElementById("filter-section-manual").value;
  const room = document.getElementById("filter-room-manual").value;

  const scheduleCards = document.querySelectorAll(
    "#schedule-grid .schedule-card"
  );
  const dropZones = document.querySelectorAll("#schedule-grid .drop-zone");

  console.log("Filtering with:", { yearLevel, section, room });

  scheduleCards.forEach((card) => {
    const cardYearLevel = card.getAttribute("data-year-level");
    const cardSectionName = card.getAttribute("data-section-name");
    const cardRoomName = card.getAttribute("data-room-name");

    const matchesYear = !yearLevel || cardYearLevel === yearLevel;
    const matchesSection = !section || cardSectionName === section;
    const matchesRoom = !room || cardRoomName === room;

    if (matchesYear && matchesSection && matchesRoom) {
      card.style.display = "block";
      card.parentElement.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });

  // Update add buttons visibility
  dropZones.forEach((zone) => {
    const card = zone.querySelector(".schedule-card");
    const addButton = zone.querySelector(
      'button[onclick*="openAddModalForSlot"]'
    );

    if (addButton) {
      const hasVisibleCard = card && card.style.display !== "none";
      addButton.style.display = hasVisibleCard ? "none" : "block";
    }
  });

  // Also update list view filters
  updateListViewWithFilters(yearLevel, section, room);
}

// Helper function to filter list view
function updateListViewWithFilters(yearLevel, section, room) {
  const rows = document.querySelectorAll("#list-view .schedule-row");

  rows.forEach((row) => {
    const scheduleId = row.dataset.scheduleId;
    const schedule = window.scheduleData.find(
      (s) => s.schedule_id == scheduleId
    );

    if (schedule) {
      const matchesYear = !yearLevel || schedule.year_level === yearLevel;
      const matchesSection = !section || schedule.section_name === section;
      const matchesRoom = !room || (schedule.room_name || "Online") === room;

      row.style.display =
        matchesYear && matchesSection && matchesRoom ? "" : "none";
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
  console.log("Enhanced manual schedules JS loaded");
  console.log("Manual schedules JS loaded");
  console.log("Current semester:", window.currentSemester);
  console.log("Schedule data count:", window.scheduleData?.length || 0);
  console.log("🔄 Initializing Enhanced Manual Schedule System...");

  // Force clear any corrupted schedule data
  if (window.scheduleData && window.scheduleData.length > 100) {
    console.log("Cleaning up potentially corrupted schedule data...");
    window.scheduleData = window.scheduleData.filter((s) => s.schedule_id);
  }

  buildCurrentSemesterCourseMappings();
  initializeDragAndDrop();
  initializeEnhancedDragAndDrop();
  setupEnhancedEventListeners();

  // Modal click outside to close
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });
  }

  // Delete modal click outside to close
  const deleteModal = document.getElementById("delete-confirmation-modal");
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) closeDeleteModal();
    });
  }

  // Real-time validation setup
  const facultySelect = document.getElementById("faculty-name");
  if (facultySelect) {
    facultySelect.addEventListener("change", (e) => {
      validateFieldRealTime("faculty_name", e.target.value, {
        day_of_week: document.getElementById("day-select")?.value || "",
        start_time: document.getElementById("start-time")?.value + ":00" || "",
        end_time: document.getElementById("end-time")?.value + ":00" || "",
      });
    });
  }

  const roomSelect = document.getElementById("room-name");
  if (roomSelect) {
    roomSelect.addEventListener("change", (e) => {
      validateFieldRealTime("room_name", e.target.value, {
        day_of_week: document.getElementById("day-select")?.value || "",
        start_time: document.getElementById("start-time")?.value + ":00" || "",
        end_time: document.getElementById("end-time")?.value + ":00" || "",
      });
    });
  }

  const sectionSelect = document.getElementById("section-name");
  if (sectionSelect) {
    sectionSelect.addEventListener("change", (e) => {
      validateFieldRealTime("section_name", e.target.value);
      handleSectionChange();
    });
  }

  const daySelect = document.getElementById("day-select");
  if (daySelect) {
    daySelect.addEventListener("change", (e) => {
      updateDayField();
      validateFieldRealTime("day_of_week", e.target.value, {
        faculty_name: document.getElementById("faculty-name")?.value || "",
        room_name: document.getElementById("room-name")?.value || "",
        start_time: document.getElementById("start-time")?.value + ":00" || "",
        end_time: document.getElementById("end-time")?.value + ":00" || "",
      });
    });
  }

  const startTimeSelect = document.getElementById("start-time");
  if (startTimeSelect) {
    startTimeSelect.addEventListener("change", (e) => {
      updateTimeFields();
      validateFieldRealTime("start_time", e.target.value + ":00", {
        day_of_week: document.getElementById("day-select")?.value || "",
        faculty_name: document.getElementById("faculty-name")?.value || "",
        room_name: document.getElementById("room-name")?.value || "",
      });
    });
  }

  const endTimeSelect = document.getElementById("end-time");
  if (endTimeSelect) {
    endTimeSelect.addEventListener("change", (e) => {
      updateTimeFields();
      validateFieldRealTime("end_time", e.target.value + ":00", {
        day_of_week: document.getElementById("day-select")?.value || "",
        start_time: document.getElementById("start-time")?.value + ":00" || "",
        faculty_name: document.getElementById("faculty-name")?.value || "",
        room_name: document.getElementById("room-name")?.value || "",
      });
    });
  }

  // Add course conflict detection event listeners
  const courseCodeInput = document.getElementById("course-code");
  if (courseCodeInput) {
    courseCodeInput.addEventListener("blur", syncCourseName);
    courseCodeInput.addEventListener("input", function () {
      // Clear conflict warning when user starts typing
      resetConflictField("course-code");
      resetConflictField("course-name");
    });
  }

  const courseNameInput = document.getElementById("course-name");
  if (courseNameInput) {
    courseNameInput.addEventListener("blur", syncCourseCode);
    courseNameInput.addEventListener("input", function () {
      // Clear conflict warning when user starts typing
      resetConflictField("course-code");
      resetConflictField("course-name");
    });
  }

  // Ensure scheduleData is always an array
  if (!Array.isArray(window.scheduleData)) window.scheduleData = [];

  // Use the new refresh function
  setTimeout(() => {
    refreshScheduleUI();
  }, 1000);

  console.log("Manual schedules initialized successfully");

  console.log("Enhanced manual schedules initialized successfully");
  console.log(
    "Available courses for current semester:",
    Object.keys(currentSemesterCourses).length
  );
});

// Enhanced course conflict detection with friendly messages
function validateCourseConflict(
  courseCode,
  courseName,
  currentScheduleId = null
) {
  if (!courseCode || !window.scheduleData || window.scheduleData.length === 0) {
    return null;
  }

  const currentSemesterId = window.currentSemester?.semester_id;
  const enteredCode = courseCode.trim().toUpperCase();
  const enteredName = courseName.trim().toLowerCase();

  // Check if course exists in curriculum
  if (!currentSemesterCourses[enteredCode] && enteredCode) {
    displayConflictWarning(
      "course-code",
      "⚠️ This course code is not in the current semester curriculum. Please verify the code or check if this course should be added to the curriculum.",
      "warning"
    );
  } else {
    removeConflictWarning("course-code");
  }

  // Check for duplicate course codes in the same semester
  const duplicateCourses = window.scheduleData.filter((schedule) => {
    if (schedule.semester_id != currentSemesterId) return false;
    if (currentScheduleId && schedule.schedule_id == currentScheduleId)
      return false;

    const scheduleCode = schedule.course_code?.trim().toUpperCase();
    const scheduleName = schedule.course_name?.trim().toLowerCase();

    return scheduleCode === enteredCode || scheduleName === enteredName;
  });

  if (duplicateCourses.length > 0) {
    const conflictCount = duplicateCourses.length;
    const sampleConflicts = duplicateCourses
      .slice(0, 2)
      .map(
        (s) =>
          `${s.section_name} (${s.day_of_week} ${formatTime(
            s.start_time?.substring(0, 5)
          )}-${formatTime(s.end_time?.substring(0, 5))})`
      )
      .join(", ");

    const extraCount =
      conflictCount > 2 ? ` and ${conflictCount - 2} more` : "";

    const message = `📚 This course is already scheduled in ${conflictCount} section(s): ${sampleConflicts}${extraCount}. Duplicate scheduling may cause conflicts.`;

    return {
      type: "course_duplicate",
      message: message,
      conflicts: duplicateCourses,
      warningLevel: "warning", // This is a warning, not an error
    };
  }

  return null;
}

// Add reset function for specific fields
function resetConflictField(fieldId) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.classList.remove("border-red-500", "bg-red-50");
    field.classList.add("border-gray-300");

    const tooltip = field.parentNode.querySelector(".conflict-tooltip");
    if (tooltip) tooltip.remove();
  }
}

// Enhanced conflict display with friendly messages
// Enhanced conflict display with better formatting
function displayConflictWarning(fieldId, message, warningLevel = "warning") {
  const field = document.getElementById(fieldId);
  if (!field) return;

  // Remove existing warning
  removeConflictWarning(fieldId);

  // Add appropriate styling
  const styles = {
    error: {
      border: "border-red-500 bg-red-50",
      text: "text-red-600",
      icon: "fa-exclamation-circle",
    },
    warning: {
      border: "border-yellow-500 bg-yellow-50",
      text: "text-yellow-600",
      icon: "fa-exclamation-triangle",
    },
    success: {
      border: "border-green-500 bg-green-50",
      text: "text-green-600",
      icon: "fa-check-circle",
    },
  };

  const style = styles[warningLevel] || styles.warning;

  field.classList.add(...style.border.split(" "));
  field.classList.remove(
    "border-gray-300",
    "bg-red-50",
    "bg-yellow-50",
    "bg-green-50"
  );

  // Create warning message element
  const warningDiv = document.createElement("div");
  warningDiv.className = `conflict-warning ${style.text} text-xs mt-1 flex items-start space-x-1`;
  warningDiv.innerHTML = `
        <i class="fas ${style.icon} mt-0.5 flex-shrink-0"></i>
        <span class="flex-1 whitespace-pre-line">${message}</span>
    `;

  field.parentNode.appendChild(warningDiv);
}

// Remove specific warning
function removeConflictWarning(fieldId) {
  const field = document.getElementById(fieldId);
  if (!field) return;

  const parent = field.parentNode;
  const existingWarning = parent.querySelector(".conflict-warning");
  if (existingWarning) {
    existingWarning.remove();
  }

  // Reset field styling
  field.classList.remove(
    "border-red-500",
    "bg-red-50",
    "border-yellow-500",
    "bg-yellow-50"
  );
  field.classList.add("border-gray-300");
  field.style.backgroundColor = "";
}

// Delete All Schedules Functionality
function deleteAllSchedules() {
  console.log("Delete All button clicked");

  // Check if there are any schedules to delete
  const currentSemesterId = window.currentSemester?.semester_id;
  const currentSemesterSchedules = window.scheduleData.filter(
    (s) => s.semester_id == currentSemesterId
  );

  if (currentSemesterSchedules.length === 0) {
    showNotification("No schedules found to delete.", "error");
    return;
  }

  // Show confirmation modal
  openDeleteModal();
}

function openDeleteModal() {
  console.log("Opening delete confirmation modal...");
  const modal = document.getElementById("delete-confirmation-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    console.log("Delete confirmation modal opened successfully");
  } else {
    console.error("Delete confirmation modal element not found!");
    // Fallback: use browser confirmation
    if (
      confirm(
        "Are you sure you want to delete ALL schedules? This action cannot be undone."
      )
    ) {
      confirmDeleteAllSchedules();
    }
  }
}

function closeDeleteModal() {
  console.log("Closing delete modal...");
  const modal = document.getElementById("delete-confirmation-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

// Enhanced event listeners setup
function setupEnhancedEventListeners() {
  console.log("🔧 Setting up enhanced event listeners...");

  // Modal event listeners
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });
  }

  // Delete modal event listeners
  const deleteModal = document.getElementById("delete-confirmation-modal");
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) closeDeleteModal();
    });
  }

  // Real-time validation setup
  const facultySelect = document.getElementById("faculty-name");
  if (facultySelect) {
    facultySelect.addEventListener("change", (e) => {
      validateFieldRealTime("faculty_name", e.target.value, getRelatedFields());
    });
  }

  const roomSelect = document.getElementById("room-name");
  if (roomSelect) {
    roomSelect.addEventListener("change", (e) => {
      validateFieldRealTime("room_name", e.target.value, getRelatedFields());
    });
  }

  const sectionSelect = document.getElementById("section-name");
  if (sectionSelect) {
    sectionSelect.addEventListener("change", (e) => {
      validateFieldRealTime("section_name", e.target.value, getRelatedFields());
      handleSectionChange();
    });
  }

  const daySelect = document.getElementById("day-select");
  if (daySelect) {
    daySelect.addEventListener("change", (e) => {
      updateDayField();
      validateFieldRealTime("day_of_week", e.target.value, getRelatedFields());
    });
  }

  const startTimeSelect = document.getElementById("start-time");
  if (startTimeSelect) {
    startTimeSelect.addEventListener("change", (e) => {
      updateTimeFields();
      validateFieldRealTime(
        "start_time",
        e.target.value + ":00",
        getRelatedFields()
      );
    });
  }

  const endTimeSelect = document.getElementById("end-time");
  if (endTimeSelect) {
    endTimeSelect.addEventListener("change", (e) => {
      updateTimeFields();
      validateFieldRealTime(
        "end_time",
        e.target.value + ":00",
        getRelatedFields()
      );
    });
  }

  // Course code validation
  const courseCodeInput = document.getElementById("course-code");
  if (courseCodeInput) {
    courseCodeInput.addEventListener("blur", syncCourseName);
    courseCodeInput.addEventListener("input", function () {
      resetConflictField("course-code");
      resetConflictField("course-name");
    });
  }

  // Course name validation
  const courseNameInput = document.getElementById("course-name");
  if (courseNameInput) {
    courseNameInput.addEventListener("blur", syncCourseCode);
    courseNameInput.addEventListener("input", function () {
      resetConflictField("course-code");
      resetConflictField("course-name");
    });
  }
}

// Helper to get related fields for comprehensive validation
function getRelatedFields() {
  const form = document.getElementById("schedule-form");
  if (!form) return {};

  const formData = new FormData(form);
  return {
    day_of_week:
      formData.get("day_of_week") ||
      document.getElementById("day-select")?.value ||
      "",
    start_time: (document.getElementById("start-time")?.value || "") + ":00",
    end_time: (document.getElementById("end-time")?.value || "") + ":00",
    faculty_name: formData.get("faculty_name") || "",
    room_name: formData.get("room_name") || "",
    section_name: formData.get("section_name") || "",
  };
}

// Enhanced time calculation for academic scheduling
function calculateAutoEndTime() {
  const startTimeInput = document.getElementById("start-time");
  const endTimeInput = document.getElementById("end-time");

  if (!startTimeInput || !startTimeInput.value) return;

  const startTime = startTimeInput.value;
  const [hours, minutes] = startTime.split(":").map(Number);

  // Default to 1 hour duration
  let durationMinutes = 60;

  // Check if we have course info to determine duration
  const courseCode = document.getElementById("course-code")?.value;
  if (courseCode && window.currentSemesterCourses[courseCode.toUpperCase()]) {
    const course = window.currentSemesterCourses[courseCode.toUpperCase()];
    // Calculate duration based on course units (3 units = 3 hours)
    if (course.units) {
      durationMinutes = course.units * 60;
    }
  }

  const endTime = calculateEndTime(startTime, durationMinutes);
  endTimeInput.value = endTime;

  // Update hidden fields
  updateTimeFields();

  // Show duration info
  showDurationInfo(durationMinutes);
}

// Set specific duration
function setDuration(minutes) {
  const startTimeInput = document.getElementById("start-time");
  const endTimeInput = document.getElementById("end-time");

  if (!startTimeInput || !startTimeInput.value) {
    showNotification("Please set a start time first", "warning");
    return;
  }

  const startTime = startTimeInput.value;
  const endTime = calculateEndTime(startTime, minutes);

  endTimeInput.value = endTime;
  updateTimeFields();
  showDurationInfo(minutes);
}

// Show duration information
function showDurationInfo(minutes) {
  const hours = minutes / 60;
  let existingInfo = document.getElementById("duration-info");

  if (!existingInfo) {
    existingInfo = document.createElement("div");
    existingInfo.id = "duration-info";
    existingInfo.className =
      "mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-sm";
    document.getElementById("end-time").parentNode.appendChild(existingInfo);
  }

  existingInfo.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="text-blue-700">
                <i class="fas fa-clock mr-1"></i>
                Duration: ${hours} hour${
    hours !== 1 ? "s" : ""
  } (${minutes} minutes)
            </span>
            <span class="text-blue-600 font-medium">
                ${hours} unit${hours !== 1 ? "s" : ""}
            </span>
        </div>
    `;
}

// Enhanced time validation
function validateTimeInput(input) {
  const timeValue = input.value.trim();

  if (!timeValue) return true;

  // Allow HH:MM format
  if (timeValue.includes(":")) {
    const [hours, minutes] = timeValue.split(":").map(Number);
    if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
      return true;
    }
  }

  // Allow HHMM format
  if (/^\d{3,4}$/.test(timeValue)) {
    const timeStr = timeValue.padStart(4, "0");
    const hours = parseInt(timeStr.substring(0, 2));
    const minutes = parseInt(timeStr.substring(2, 4));

    if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
      // Auto-format to HH:MM
      input.value = `${hours.toString().padStart(2, "0")}:${minutes
        .toString()
        .padStart(2, "0")}`;
      return true;
    }
  }

  // Invalid format
  showNotification(
    "Please enter time in HH:MM format (e.g., 07:30 or 730)",
    "error"
  );
  input.focus();
  return false;
}

// Enhanced function to edit schedule from ANY cell
function editScheduleFromAnyCell(scheduleId) {
  console.log("✏️ Editing schedule from any cell:", scheduleId);

  // Find the schedule in the data
  const schedule = window.scheduleData.find((s) => s.schedule_id == scheduleId);
  if (!schedule) {
    console.error("Schedule not found:", scheduleId);
    showNotification("Schedule not found", "error");
    return;
  }

  // Use the existing edit function
  editSchedule(scheduleId);
}

// Enhanced CSS injection for better visual distinction
const enhancedStyles = `
    .full-access-card {
        opacity: 1 !important;
        cursor: move !important;
    }
    
    .full-access-card:hover {
        opacity: 1 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 100;
    }
    
    .schedule-card.dragging {
        opacity: 0.6 !important;
        transform: rotate(5deg);
    }
    
    .drop-zone.drag-over {
        background: rgba(34, 197, 94, 0.1) !important;
        border: 2px dashed #22c55e !important;
    }
    
    /* Ensure all action buttons are visible */
    .schedule-card button {
        opacity: 0.8 !important;
        display: block !important;
    }
    
    .schedule-card:hover button {
        opacity: 1 !important;
    }
    
    /* Remove any opacity restrictions */
    .schedule-card {
        opacity: 1 !important;
    }
`;

// Inject the enhanced styles
const styleElement = document.createElement("style");
styleElement.textContent = enhancedScheduleStyles;
document.head.appendChild(styleElement);

// Initialize everything
function initializeManualScheduleSystem() {
  console.log("🔄 Initializing Enhanced Manual Schedule System...");

  buildCurrentSemesterCourseMappings();
  initializeEnhancedDragAndDrop();
  setupEventListeners();

  // Make sure ALL schedule cards are fully accessible
  setTimeout(() => {
    enableFullAccessToAllScheduleCards();
  }, 500);
}

// Enhanced function to make ALL schedule cards fully accessible
function enableFullAccessToAllScheduleCards() {
  const allScheduleCards = document.querySelectorAll(".schedule-card");
  console.log(
    `🎯 Enabling full access for ${allScheduleCards.length} schedule cards`
  );

  allScheduleCards.forEach((card) => {
    // Remove any restrictions and make fully accessible
    card.classList.add("full-access-card");
    card.setAttribute("data-full-access", "true");
    card.draggable = true;

    // Remove any "(cont.)" labels and make all cards look the same
    const contSpan = card.querySelector("span.text-gray-500");
    if (contSpan) {
      contSpan.remove();
    }

    // Ensure full opacity for all cards
    card.style.opacity = "1";

    // Make sure the card itself is draggable
    card.addEventListener("dragstart", handleEnhancedDragStart);
    card.addEventListener("dragend", handleEnhancedDragEnd);

    // 🚀 FORCE action buttons to be visible
    forceActionButtonsVisibility();

    // Ensure edit/delete buttons work
    const editBtn = card.querySelector('button[onclick*="editSchedule"]');
    const deleteBtn = card.querySelector(
      'button[onclick*="openDeleteSingleModal"]'
    );

    if (editBtn) {
      editBtn.style.display = "block";
      editBtn.style.opacity = "1";
      editBtn.onclick = function (e) {
        e.stopPropagation();
        const scheduleId = card.dataset.scheduleId;
        console.log("Editing schedule from ANY cell:", scheduleId);
        editScheduleFromAnyCell(scheduleId);
      };
    }

    if (deleteBtn) {
      deleteBtn.style.display = "block";
      deleteBtn.style.opacity = "1";
      deleteBtn.onclick = function (e) {
        e.stopPropagation();
        const scheduleId = card.dataset.scheduleId;
        const schedule = window.scheduleData.find(
          (s) => s.schedule_id == scheduleId
        );
        if (schedule) {
          openDeleteSingleModal(
            scheduleId,
            schedule.course_code,
            schedule.section_name,
            schedule.day_of_week,
            formatTime(schedule.start_time?.substring(0, 5) || ""),
            formatTime(schedule.end_time?.substring(0, 5) || "")
          );
        }
      };
    }

    // Make sure the card itself is draggable
    card.addEventListener("dragstart", handleEnhancedDragStart);
    card.addEventListener("dragend", handleEnhancedDragEnd);
  });
}

// Enhanced drag start - works for ALL cards
function handleEnhancedDragStart(e) {
  draggedElement = e.target.closest(".schedule-card");
  if (!draggedElement) return;

  const scheduleId = draggedElement.dataset.scheduleId;
  console.log("🔄 Dragging schedule:", scheduleId);

  e.dataTransfer.setData("text/plain", scheduleId);
  e.dataTransfer.effectAllowed = "move";
  draggedElement.classList.add("dragging");

  // Store original position for reference
  const originalCell = draggedElement.closest(".schedule-cell");
  if (originalCell) {
    draggedElement.dataset.originalDay = originalCell.dataset.day;
    draggedElement.dataset.originalStartTime = originalCell.dataset.startTime;
  }

  setTimeout(() => {
    draggedElement.style.opacity = "0.4";
  }, 0);
}

// Enhanced drag end
function handleEnhancedDragEnd(e) {
  if (draggedElement) {
    draggedElement.style.opacity = "1";
    draggedElement.classList.remove("dragging");
  }
  draggedElement = null;

  // Remove drag-over class from all drop zones
  document.querySelectorAll(".drop-zone.drag-over").forEach((zone) => {
    zone.classList.remove("drag-over");
  });
}

// Enhanced drag enter
function handleEnhancedDragEnter(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.target.classList.add("drag-over");
    e.preventDefault();
  }
}

// Enhanced drag over
function handleEnhancedDragOver(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";
  }
}

// Enhanced drag leave
function handleEnhancedDragLeave(e) {
  if (e.target.classList.contains("drop-zone")) {
    e.target.classList.remove("drag-over");
  }
}

// Enhanced drop handler
function handleEnhancedDrop(e) {
  e.preventDefault();
  const dropZone = e.target.closest(".drop-zone");
  if (!dropZone || !draggedElement) return;

  dropZone.classList.remove("drag-over");
  const scheduleId = e.dataTransfer.getData("text/plain");
  const newDay = dropZone.dataset.day;
  const newSlotStartTime = dropZone.dataset.startTime;

  console.log(
    "🎯 Dropping schedule:",
    scheduleId,
    "to",
    newDay,
    "at slot",
    newSlotStartTime
  );

  const scheduleIndex = window.scheduleData.findIndex(
    (s) => s.schedule_id == scheduleId
  );

  if (scheduleIndex !== -1) {
    const originalSchedule = window.scheduleData[scheduleIndex];

    // Calculate new times based on the original duration
    const originalStart = new Date(`2000-01-01T${originalSchedule.start_time}`);
    const originalEnd = new Date(`2000-01-01T${originalSchedule.end_time}`);
    const durationMinutes = (originalEnd - originalStart) / (1000 * 60);

    // Use the slot start time as the new start time
    const newStart = new Date(`2000-01-01T${newSlotStartTime}:00`);
    const newEnd = new Date(newStart.getTime() + durationMinutes * 60000);

    const formattedStartTime = newStart.toTimeString().substring(0, 8);
    const formattedEndTime = newEnd.toTimeString().substring(0, 8);

    console.log("⏰ Time update:", {
      original: `${originalSchedule.start_time} - ${originalSchedule.end_time}`,
      new: `${formattedStartTime} - ${formattedEndTime}`,
      duration: durationMinutes + " minutes",
    });

    // Update the schedule data
    window.scheduleData[scheduleIndex] = {
      ...window.scheduleData[scheduleIndex],
      day_of_week: newDay,
      start_time: formattedStartTime,
      end_time: formattedEndTime,
    };

    // Refresh the entire display
    refreshScheduleGrid();
    showNotification(
      `✅ Schedule moved to ${newDay} ${formattedStartTime.substring(
        0,
        5
      )}-${formattedEndTime.substring(0, 5)}`,
      "success"
    );
  }
}

// Refresh the entire grid
function refreshScheduleGrid() {
  console.log("🔄 Refreshing schedule grid...");

  // Show loading state
  const grid = document.getElementById("schedule-grid");
  if (grid) {
    grid.style.opacity = "0.7";
  }

  // Re-render with current data
  setTimeout(() => {
    safeUpdateScheduleDisplay(window.scheduleData);
    refreshScheduleUI(); // Use the new refresh function

    if (grid) {
      grid.style.opacity = "1";
    }
  }, 300);
}

// Initialize enhanced drag and drop
function initializeEnhancedDragAndDrop() {
  const dropZones = document.querySelectorAll(".drop-zone");
  const draggables = document.querySelectorAll(".schedule-card");

  console.log("🎯 Initializing enhanced drag & drop:", {
    dropZones: dropZones.length,
    draggables: draggables.length,
  });

  // Setup drop zones
  dropZones.forEach((zone) => {
    zone.removeEventListener("dragover", handleEnhancedDragOver);
    zone.removeEventListener("dragenter", handleEnhancedDragEnter);
    zone.removeEventListener("dragleave", handleEnhancedDragLeave);
    zone.removeEventListener("drop", handleEnhancedDrop);

    zone.addEventListener("dragover", handleEnhancedDragOver);
    zone.addEventListener("dragenter", handleEnhancedDragEnter);
    zone.addEventListener("dragleave", handleEnhancedDragLeave);
    zone.addEventListener("drop", handleEnhancedDrop);
  });

  // Setup draggables
  draggables.forEach((draggable) => {
    draggable.removeEventListener("dragstart", handleEnhancedDragStart);
    draggable.removeEventListener("dragend", handleEnhancedDragEnd);

    draggable.addEventListener("dragstart", handleEnhancedDragStart);
    draggable.addEventListener("dragend", handleEnhancedDragEnd);
  });
}

// Setup event listeners
function setupEventListeners() {
  console.log("🔧 Setting up event listeners...");

  // Modal event listeners
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });
  }

  // Real-time validation setup
  setupValidationEventListeners();
}

// 🚀 FORCE action buttons to appear on ALL schedule cards
function forceActionButtonsVisibility() {
  console.log("🔧 Forcing action buttons visibility...");

  const allScheduleCards = document.querySelectorAll(".schedule-card");

  allScheduleCards.forEach((card) => {
    // Ensure the action buttons container exists
    let actionContainer = card.querySelector(
      ".flex.space-x-1.flex-shrink-0.ml-1"
    );

    if (!actionContainer) {
      // Create action buttons container if it doesn't exist
      actionContainer = document.createElement("div");
      actionContainer.className = "flex space-x-1 flex-shrink-0 ml-1";

      const scheduleId = card.dataset.scheduleId;
      const schedule = window.scheduleData.find(
        (s) => s.schedule_id == scheduleId
      );

      if (schedule) {
        actionContainer.innerHTML = `
                    <button onclick="event.stopPropagation(); editScheduleFromAnyCell('${scheduleId}')" 
                            class="text-yellow-600 hover:text-yellow-700 no-print">
                        <i class="fas fa-edit text-xs"></i>
                    </button>
                    <button onclick="event.stopPropagation(); openDeleteSingleModal(
                        '${scheduleId}', 
                        '${schedule.course_code || ""}', 
                        '${schedule.section_name || ""}', 
                        '${schedule.day_of_week || ""}', 
                        '${
                          schedule.start_time
                            ? formatTime(schedule.start_time.substring(0, 5))
                            : ""
                        }', 
                        '${
                          schedule.end_time
                            ? formatTime(schedule.end_time.substring(0, 5))
                            : ""
                        }'
                    )" class="text-red-600 hover:text-red-700 no-print">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                `;

        // Find the title container and append action buttons
        const titleContainer = card.querySelector(
          ".flex.justify-between.items-start.mb-1"
        );
        if (titleContainer) {
          titleContainer.appendChild(actionContainer);
        }
      }
    }

    // Force display and opacity of all buttons
    const buttons = card.querySelectorAll("button");
    buttons.forEach((button) => {
      button.style.display = "inline-block";
      button.style.opacity = "1";
    });
  });

  console.log(`✅ Action buttons forced on ${allScheduleCards.length} cards`);
}

// Call this function after any DOM updates
function refreshScheduleUI() {
  console.log("🔄 Refreshing schedule UI...");

  // Small delay to ensure DOM is ready
  setTimeout(() => {
    forceActionButtonsVisibility();
    enableFullAccessToAllScheduleCards();
    initializeEnhancedDragAndDrop();
  }, 100);
}

// Debug function to check button visibility
function debugButtonVisibility() {
  console.log("🔍 Debugging button visibility...");

  const cards = document.querySelectorAll(".schedule-card");
  cards.forEach((card, index) => {
    const scheduleId = card.dataset.scheduleId;
    const buttons = card.querySelectorAll("button");
    const actionContainer = card.querySelector(
      ".flex.space-x-1.flex-shrink-0.ml-1"
    );

    console.log(`Card ${index + 1} (ID: ${scheduleId}):`, {
      buttonsCount: buttons.length,
      hasActionContainer: !!actionContainer,
      actionContainerHTML: actionContainer
        ? actionContainer.innerHTML
        : "MISSING",
    });
  });
}

// 🎯 ACCURATE TIME SLOT MANAGEMENT
function generateAccurateTimeSlots(schedules) {
  console.log("🎯 Generating accurate time slots...");

  const allTimes = new Set();

  // Add ALL unique start and end times from schedules
  schedules.forEach((schedule) => {
    if (schedule.start_time) {
      const startTime = schedule.start_time.substring(0, 5);
      allTimes.add(startTime);
    }
    if (schedule.end_time) {
      const endTime = schedule.end_time.substring(0, 5);
      allTimes.add(endTime);
    }
  });

  // Add base time points for coverage
  const baseTimes = [
    "07:00",
    "07:30",
    "08:00",
    "08:30",
    "09:00",
    "09:30",
    "10:00",
    "10:30",
    "11:00",
    "11:30",
    "12:00",
    "12:30",
    "13:00",
    "13:30",
    "14:00",
    "14:30",
    "15:00",
    "15:30",
    "16:00",
    "16:30",
    "17:00",
    "17:30",
    "18:00",
    "18:30",
    "19:00",
    "19:30",
    "20:00",
  ];

  baseTimes.forEach((time) => allTimes.add(time));

  // Convert to array and sort
  const sortedTimes = Array.from(allTimes).sort((a, b) => {
    return new Date(`2000-01-01T${a}:00`) - new Date(`2000-01-01T${b}:00`);
  });

  // Create time slots
  const timeSlots = [];
  for (let i = 0; i < sortedTimes.length - 1; i++) {
    timeSlots.push({
      start: sortedTimes[i],
      end: sortedTimes[i + 1],
    });
  }

  console.log("Generated time slots:", timeSlots);
  return timeSlots;
}

// 🎯 Calculate proper row height based on duration
function calculateRowHeight(startTime, endTime) {
  const start = new Date(`2000-01-01T${startTime}:00`);
  const end = new Date(`2000-01-01T${endTime}:00`);
  const duration = (end - start) / (1000 * 60); // duration in minutes

  // Base height + proportional scaling (40px per 30 minutes)
  return Math.max(60, (duration / 30) * 40);
}

// 🎯 Enhanced schedule display with accurate time slots
function updateScheduleGridWithAccurateSlots(schedules) {
  const grid = document.getElementById("schedule-grid");
  if (!grid) return;

  grid.innerHTML = "";

  const timeSlots = generateAccurateTimeSlots(schedules);
  const days = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ];

  timeSlots.forEach((slot) => {
    const rowHeight = calculateRowHeight(slot.start, slot.end);
    const row = document.createElement("div");
    row.className = `grid grid-cols-7 hover:bg-gray-50 transition-colors duration-200 schedule-row`;
    row.style.minHeight = `${rowHeight}px`;

    // Time Column
    const timeCell = document.createElement("div");
    timeCell.className =
      "px-3 py-3 text-sm font-medium text-gray-600 border-r border-gray-200 bg-gray-50 sticky left-0 z-10 flex items-center";
    timeCell.style.minHeight = `${rowHeight}px`;

    const duration =
      (new Date(`2000-01-01T${slot.end}:00`) -
        new Date(`2000-01-01T${slot.start}:00`)) /
      (1000 * 60);

    timeCell.innerHTML = `
            <div>
                <span class="text-sm hidden sm:block">
                    ${formatTime(slot.start)} - ${formatTime(slot.end)}
                </span>
                <span class="text-xs sm:hidden">
                    ${slot.start}-${slot.end}
                </span>
                <br>
                <span class="text-xs text-gray-500 hidden sm:inline">
                    (${duration} min)
                </span>
            </div>
        `;
    row.appendChild(timeCell);

    // Day Columns
    days.forEach((day) => {
      const cell = document.createElement("div");
      cell.className =
        "px-1 py-1 border-r border-gray-200 last:border-r-0 relative drop-zone schedule-cell";
      cell.dataset.day = day;
      cell.dataset.startTime = slot.start;
      cell.dataset.endTime = slot.end;
      cell.style.minHeight = `${rowHeight}px`;

      // Find schedules that start EXACTLY in this time slot
      const schedulesForSlot = schedules.filter((schedule) => {
        if (schedule.day_of_week !== day) return false;

        const scheduleStart = schedule.start_time
          ? schedule.start_time.substring(0, 5)
          : "";
        return scheduleStart === slot.start;
      });

      if (schedulesForSlot.length > 0) {
        const schedulesContainer = document.createElement("div");
        schedulesContainer.className = "space-y-1 h-full";

        schedulesForSlot.forEach((schedule) => {
          const scheduleCard = createAccurateScheduleCard(schedule, slot);
          schedulesContainer.appendChild(scheduleCard);
        });

        cell.appendChild(schedulesContainer);
      } else {
        // Empty slot - show add button
        const addButton = document.createElement("button");
        addButton.innerHTML = '<i class="fas fa-plus text-sm"></i>';
        addButton.className =
          "w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print flex items-center justify-center";
        addButton.style.minHeight = `${rowHeight - 16}px`;
        addButton.onclick = () =>
          openAddModalForSlot(day, slot.start, slot.end);
        cell.appendChild(addButton);
      }

      row.appendChild(cell);
    });

    grid.appendChild(row);
  });

  // Initialize enhanced functionality
  setTimeout(() => {
    enableFullAccessToAllScheduleCards();
    initializeEnhancedDragAndDrop();
  }, 100);
}

// 🎯 Create accurate schedule card with proper height
function createAccurateScheduleCard(schedule, timeSlot) {
  const card = document.createElement("div");

  const scheduleStart = new Date(`2000-01-01T${schedule.start_time}`);
  const scheduleEnd = new Date(`2000-01-01T${schedule.end_time}`);
  const scheduleDuration = (scheduleEnd - scheduleStart) / (1000 * 60);
  const scheduleHeight = Math.max(60, (scheduleDuration / 30) * 40);

  const colors = [
    "bg-blue-100 border-blue-300 text-blue-800",
    "bg-green-100 border-green-300 text-green-800",
    "bg-purple-100 border-purple-300 text-purple-800",
    "bg-orange-100 border-orange-300 text-orange-800",
    "bg-pink-100 border-pink-300 text-pink-800",
  ];

  const colorIndex = schedule.schedule_id
    ? parseInt(schedule.schedule_id) % colors.length
    : Math.floor(Math.random() * colors.length);
  const colorClass = colors[colorIndex];

  card.className = `schedule-card ${colorClass} p-2 rounded-lg border-l-4 draggable cursor-move text-xs full-access-card`;
  card.draggable = true;
  card.dataset.scheduleId = schedule.schedule_id || "";
  card.dataset.yearLevel = schedule.year_level || "";
  card.dataset.sectionName = schedule.section_name || "";
  card.dataset.roomName = schedule.room_name || "Online";
  card.dataset.startTime = schedule.start_time;
  card.dataset.endTime = schedule.end_time;
  card.dataset.fullAccess = "true";
  card.style.minHeight = `${scheduleHeight - 16}px`;

  card.innerHTML = `
        <div class="flex justify-between items-start mb-1">
            <div class="font-semibold truncate flex-1">
                ${schedule.course_code || ""}
            </div>
            <div class="flex space-x-1 flex-shrink-0 ml-1">
                <button onclick="event.stopPropagation(); editScheduleFromAnyCell('${
                  schedule.schedule_id || ""
                }')" 
                        class="text-yellow-600 hover:text-yellow-700 no-print">
                    <i class="fas fa-edit text-xs"></i>
                </button>
                <button onclick="event.stopPropagation(); openDeleteSingleModal(
                    '${schedule.schedule_id || ""}', 
                    '${schedule.course_code || ""}', 
                    '${schedule.section_name || ""}', 
                    '${schedule.day_of_week || ""}', 
                    '${
                      schedule.start_time
                        ? formatTime(schedule.start_time.substring(0, 5))
                        : ""
                    }', 
                    '${
                      schedule.end_time
                        ? formatTime(schedule.end_time.substring(0, 5))
                        : ""
                    }'
                )" class="text-red-600 hover:text-red-700 no-print">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
        </div>
        <div class="opacity-90 truncate">
            ${schedule.section_name || ""}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.faculty_name || ""}
        </div>
        <div class="opacity-75 truncate">
            ${schedule.room_name || "Online"}
        </div>
        <div class="font-medium mt-1 text-xs">
            ${
              schedule.start_time && schedule.end_time
                ? `${schedule.start_time.substring(
                    0,
                    5
                  )} - ${schedule.end_time.substring(0, 5)}`
                : ""
            }
        </div>
        <div class="text-xs text-gray-500 mt-1">
            Duration: ${scheduleDuration} minutes
        </div>
    `;

  return card;
}
