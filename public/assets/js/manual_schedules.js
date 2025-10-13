// Manual Schedule Management - COMPLETE FIX WITH SEMESTER FILTERING
let draggedElement = null;
let currentEditingId = null;
let currentSemesterCourses = {}; // Store courses for current semester only

function handleDragStart(e) {
  draggedElement = e.target.closest(".schedule-card");
  if (!draggedElement) return;

  e.dataTransfer.setData("text/plain", draggedElement.dataset.scheduleId);
  e.dataTransfer.effectAllowed = "move";

  // Add visual feedback
  draggedElement.classList.add("dragging");
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

  // Remove all drag-over classes
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

function calculateEndTime(startTime, durationMinutes = 60) {
  const [hours, minutes] = startTime.split(":").map(Number);
  const startDate = new Date(2000, 0, 1, hours, minutes);
  const endDate = new Date(startDate.getTime() + durationMinutes * 60000);

  return endDate.toTimeString().substring(0, 5);
}

// Enhanced handleDrop with better time calculation
function handleDrop(e) {
  e.preventDefault();
  const dropZone = e.target.closest(".drop-zone");

  if (!dropZone || !draggedElement) return;

  dropZone.classList.remove("drag-over");

  const scheduleId = e.dataTransfer.getData("text/plain");
  const newDay = dropZone.dataset.day;
  const newStartTime = dropZone.dataset.startTime;
  const newEndTime = dropZone.dataset.endTime;

  console.log(
    "Dropping schedule:",
    scheduleId,
    "to",
    newDay,
    newStartTime,
    newEndTime
  );

  // Find the schedule in our data
  const scheduleIndex = window.scheduleData.findIndex(
    (s) => s.schedule_id == scheduleId
  );

  if (scheduleIndex !== -1) {
    const originalSchedule = window.scheduleData[scheduleIndex];

    // Calculate duration from original schedule
    const originalStart = new Date(
      `2000-01-01 ${originalSchedule.start_time.substring(0, 5)}`
    );
    const originalEnd = new Date(
      `2000-01-01 ${originalSchedule.end_time.substring(0, 5)}`
    );
    const durationMinutes = (originalEnd - originalStart) / (1000 * 60);

    // Calculate new end time based on original duration
    const formattedEndTime = calculateEndTime(newStartTime, durationMinutes);

    console.log("Time update details:", {
      originalDuration: durationMinutes + " minutes",
      newStart: newStartTime,
      newEnd: formattedEndTime,
    });

    // Update the schedule data with new times
    window.scheduleData[scheduleIndex].day_of_week = newDay;
    window.scheduleData[scheduleIndex].start_time = newStartTime + ":00";
    window.scheduleData[scheduleIndex].end_time = formattedEndTime + ":00";

    // Update the display
    safeUpdateScheduleDisplay(window.scheduleData);

    showNotification(
      `Schedule moved to ${newDay} ${newStartTime}-${formattedEndTime}`,
      "success"
    );
  }
}

// Enhanced initializeDragAndDrop function
function initializeDragAndDrop() {
  const dropZones = document.querySelectorAll(".drop-zone");
  const draggables = document.querySelectorAll(".draggable");

  console.log("Initializing drag and drop:", {
    dropZones: dropZones.length,
    draggables: draggables.length,
  });

  // Remove existing event listeners to prevent duplicates
  dropZones.forEach((zone) => {
    zone.removeEventListener("dragover", handleDragOver);
    zone.removeEventListener("dragenter", handleDragEnter);
    zone.removeEventListener("dragleave", handleDragLeave);
    zone.removeEventListener("drop", handleDrop);
  });

  draggables.forEach((draggable) => {
    draggable.removeEventListener("dragstart", handleDragStart);
    draggable.removeEventListener("dragend", handleDragEnd);
  });

  // Add new event listeners
  dropZones.forEach((zone) => {
    zone.addEventListener("dragover", handleDragOver);
    zone.addEventListener("dragenter", handleDragEnter);
    zone.addEventListener("dragleave", handleDragLeave);
    zone.addEventListener("drop", handleDrop);
  });

  draggables.forEach((draggable) => {
    draggable.addEventListener("dragstart", handleDragStart);
    draggable.addEventListener("dragend", handleDragEnd);
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
    // Fallback to browser confirm
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
    '#delete-all-modal button[onclick="confirmDeleteAllSchedules()"]'
  );
  const originalText = deleteButton ? deleteButton.innerHTML : "";

  // Show loading state
  if (deleteButton) {
    deleteButton.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteButton.disabled = true;
  }

  // Make API call to delete all schedules
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
    .then((response) => {
      console.log("Delete all response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Delete all response data:", data);

      if (data.success) {
        showNotification("All schedules deleted successfully!", "success");

        // Clear local schedule data
        window.scheduleData = [];
        safeUpdateScheduleDisplay([]);

        // Hide generation results if exists
        const generationResults = document.getElementById("generation-results");
        if (generationResults) {
          generationResults.classList.add("hidden");
        }

        // Rebuild course mappings
        buildCurrentSemesterCourseMappings();
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
      // Restore button state
      if (deleteButton) {
        deleteButton.innerHTML = originalText;
        deleteButton.disabled = false;
      }
      closeDeleteAllModal();
    });
}

function deleteAllSchedules() {
  console.log("deleteAllSchedules called");
  openDeleteAllModal();
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

  // Update modal content with schedule details
  const detailsElement = document.getElementById("single-delete-details");
  if (detailsElement) {
    detailsElement.innerHTML = `
            <div class="text-sm">
                <div class="font-semibold">${courseCode} - ${sectionName}</div>
                <div class="text-gray-600 mt-1">${day} • ${startTime} to ${endTime}</div>
            </div>
        `;
  }

  const modal = document.getElementById("delete-single-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    console.log("Single delete modal opened successfully");
  } else {
    console.error("Single delete modal element not found!");
    // Fallback to browser confirm
    if (
      confirm(`Are you sure you want to delete ${courseCode} - ${sectionName}?`)
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

  // Show loading state
  if (deleteButton) {
    deleteButton.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteButton.disabled = true;
  }

  // Make API call to delete single schedule
  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "delete_schedule",
      schedule_id: currentDeleteScheduleId,
    }),
  })
    .then((response) => {
      console.log("Single delete response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Single delete response data:", data);

      if (data.success) {
        showNotification("Schedule deleted successfully!", "success");

        // Remove from local data
        window.scheduleData = window.scheduleData.filter(
          (s) => s.schedule_id != currentDeleteScheduleId
        );

        // Update display
        safeUpdateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();

        // Rebuild course mappings
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
      // Restore button state
      if (deleteButton) {
        deleteButton.innerHTML = originalText;
        deleteButton.disabled = false;
      }
      closeDeleteSingleModal();
    });
}

function deleteSchedule(scheduleId) {
  // Find the schedule details for the modal
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

function formatTime(timeString) {
  if (!timeString) return "";
  const [hours, minutes] = timeString.split(":");
  const date = new Date(2000, 0, 1, hours, minutes);
  return date.toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function showNotification(message, type = "success", duration = 5000) {
  // Remove existing notification
  const existingNotification = document.getElementById("notification");
  if (existingNotification) {
    existingNotification.remove();
  }

  const notificationDiv = document.createElement("div");
  notificationDiv.id = "notification";
  notificationDiv.className = `fixed top-4 right-4 z-50 max-w-sm w-full ${
    type === "success"
      ? "bg-green-50 border border-green-200"
      : type === "error"
      ? "bg-red-50 border border-red-200"
      : "bg-yellow-50 border border-yellow-200"
  } rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full`;

  notificationDiv.innerHTML = `
        <div class="flex p-4">
            <div class="flex-shrink-0">
                <i class="fas ${
                  type === "success"
                    ? "fa-check-circle text-green-400"
                    : type === "error"
                    ? "fa-exclamation-circle text-red-400"
                    : "fa-exclamation-triangle text-yellow-400"
                } text-lg"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium ${
                  type === "success"
                    ? "text-green-800"
                    : type === "error"
                    ? "text-red-800"
                    : "text-yellow-800"
                } whitespace-pre-line">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button class="inline-flex ${
                  type === "success"
                    ? "text-green-400 hover:text-green-600"
                    : type === "error"
                    ? "text-red-400 hover:text-red-600"
                    : "text-yellow-400 hover:text-yellow-600"
                }" onclick="this.parentElement.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

  document.body.appendChild(notificationDiv);

  // Animate in
  setTimeout(() => {
    notificationDiv.classList.remove("translate-x-full");
    notificationDiv.classList.add("translate-x-0");
  }, 100);

  // Auto remove after duration
  setTimeout(() => {
    if (notificationDiv.parentElement) {
      notificationDiv.classList.add("translate-x-full");
      setTimeout(() => {
        if (notificationDiv.parentElement) {
          notificationDiv.remove();
        }
      }, 300);
    }
  }, duration);
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

function autoFillCourseName(courseCode) {
  const courseNameInput = document.getElementById("course-name");

  if (!courseCode || !courseNameInput) return;

  const enteredCode = courseCode.trim().toUpperCase();
  console.log("Looking up course:", enteredCode);

  // Check if this code exists in our current semester mapping
  if (currentSemesterCourses[enteredCode]) {
    const course = currentSemesterCourses[enteredCode];
    courseNameInput.value = course.name;
    console.log("Found course:", course);

    // Auto-filter sections based on course year level
    setTimeout(() => {
      filterSectionsByYearLevel();
      handleSectionChange();
    }, 100);
  } else {
    console.log("Course not found in current semester");
    courseNameInput.value = "";
    // Reset section filter if course not found
    resetSectionFilter();
  }
}

// Enhanced section filtering based on curriculum year level
function autoFilterSections(yearLevel) {
  const sectionSelect = document.getElementById("section-name");
  if (!sectionSelect) return;

  // Show all options first
  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach((option) => {
    option.style.display = "";
  });

  // Hide options that don't match the year level
  // Convert year level format if needed (e.g., "1st Year" -> "1st")
  const searchYear = yearLevel.toLowerCase().replace(" year", "").trim();

  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach((optgroup) => {
    const groupYearLevel = optgroup.label
      .toLowerCase()
      .replace(" year", "")
      .trim();
    if (groupYearLevel !== searchYear) {
      const options = optgroup.querySelectorAll("option");
      options.forEach((option) => {
        option.style.display = "none";
      });
    }
  });

  // Reset selection if current selection is hidden
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
  if (selectedOption.style.display === "none") {
    sectionSelect.value = "";
  }
}

function resetSectionFilter() {
  const sectionSelect = document.getElementById("section-name");
  if (!sectionSelect) return;

  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach((option) => {
    option.style.display = "";
  });
}

// Extract year level from course code (customize this based on your course code format)
function extractYearLevelFromCourseCode(courseCode) {
  if (!courseCode) return null;

  // Example: CC101 -> 1st year, CC201 -> 2nd year, etc.
  const match = courseCode.match(/\d{3}/);
  if (match) {
    const codeNum = parseInt(match[0]);
    if (codeNum >= 100 && codeNum < 200) return "1st";
    if (codeNum >= 200 && codeNum < 300) return "2nd";
    if (codeNum >= 300 && codeNum < 400) return "3rd";
    if (codeNum >= 400 && codeNum < 500) return "4th";
  }

  return null;
}

// Update time fields when dropdowns change
function updateTimeFields() {
  const startTimeSelect = document.getElementById("start-time");
  const endTimeSelect = document.getElementById("end-time");
  const modalStartTime = document.getElementById("modal-start-time");
  const modalEndTime = document.getElementById("modal-end-time");

  if (startTimeSelect && modalStartTime) {
    modalStartTime.value = startTimeSelect.value + ":00";
  }

  if (endTimeSelect && modalEndTime) {
    modalEndTime.value = endTimeSelect.value + ":00";
  }
}

// Update day field when pattern changes
function updateDayField() {
  const daySelect = document.getElementById("day-select");
  const modalDay = document.getElementById("modal-day");

  if (daySelect && modalDay) {
    modalDay.value = daySelect.value;
  }
}

// Enhanced handleScheduleSubmit with conflict detection display
// Enhanced conflict detection with visual feedback
function handleScheduleSubmit(e) {
  e.preventDefault();
  console.log("Submitting schedule form...");

  // Reset conflict styles
  resetConflictStyles();

  // Verify we're working with current semester
  const currentSemesterId = window.currentSemester?.semester_id;
  if (!currentSemesterId) {
    showNotification("No active semester selected", "error");
    return;
  }

  // Get all form values
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
  };

  console.log("Form data:", data);

  // Validate required fields with conflict highlighting
  let hasEmptyFields = false;
  const requiredFields = [
    { id: "course-code", value: data.course_code, name: "Course Code" },
    { id: "course-name", value: data.course_name, name: "Course Name" },
    { id: "section-name", value: data.section_name, name: "Section" },
    { id: "faculty-name", value: data.faculty_name, name: "Faculty" },
    { id: "day-select", value: data.day_of_week, name: "Day Pattern" },
  ];

  requiredFields.forEach((field) => {
    if (!field.value) {
      highlightConflictField(field.id, `${field.name} is required`);
      hasEmptyFields = true;
    }
  });

  if (hasEmptyFields) {
    showNotification("Please fill out all required fields.", "error");
    return;
  }

  // Validate time logic
  if (data.start_time >= data.end_time) {
    highlightConflictField("start-time", "Start time must be before end time");
    highlightConflictField("end-time", "End time must be after start time");
    showNotification("End time must be after start time.", "error");
    return;
  }

  // Show loading state
  const submitButton = e.target.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;
  submitButton.innerHTML =
    '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
  submitButton.disabled = true;

  // Submit form to generateSchedulesAjax endpoint
  fetch("/chair/generate-schedules", {
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
        resetConflictStyles();

        let message =
          result.message ||
          (currentEditingId
            ? "Schedule updated successfully!"
            : "Schedule added successfully!");

        // Handle multiple schedules for day patterns
        if (result.schedules && result.schedules.length > 1) {
          message = `Schedules added successfully for ${result.schedules.length} days!`;

          // Add all new schedules to local data
          result.schedules.forEach((schedule) => {
            window.scheduleData.push({
              ...schedule,
              semester_id: currentSemesterId,
            });
          });
        } else if (result.schedules && result.schedules.length === 1) {
          // Single schedule (update or add)
          if (currentEditingId) {
            const index = window.scheduleData.findIndex(
              (s) => s.schedule_id == currentEditingId
            );
            if (index !== -1) {
              window.scheduleData[index] = {
                ...window.scheduleData[index],
                ...result.schedules[0],
                semester_id: currentSemesterId,
              };
            }
          } else {
            window.scheduleData.push({
              ...result.schedules[0],
              semester_id: currentSemesterId,
            });
          }
        }

        if (result.partial_success) {
          message += ` (${result.failed_days} day(s) had conflicts)`;
        }

        showNotification(message, "success");

        // Refresh display
        safeUpdateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();

        // Rebuild course mappings with new data
        buildCurrentSemesterCourseMappings();
      } else {
        // Show conflicts with field highlighting
        resetConflictStyles();

        if (result.conflicts && result.conflicts.length > 0) {
          const conflictDetails = result.conflicts.join("\n• ");

          // Highlight specific fields based on conflict type
          result.conflicts.forEach((conflict) => {
            if (conflict.includes("faculty")) {
              highlightConflictField(
                "faculty-name",
                "Faculty has scheduling conflict"
              );
            }
            if (conflict.includes("room")) {
              highlightConflictField("room-name", "Room is already occupied");
            }
            if (conflict.includes("section")) {
              highlightConflictField(
                "section-name",
                "Section has scheduling conflict"
              );
            }
            if (conflict.includes("time")) {
              highlightConflictField("start-time", "Time conflict detected");
              highlightConflictField("end-time", "Time conflict detected");
            }
          });

          showNotification(
            `Schedule conflicts detected:\n• ${conflictDetails}`,
            "error",
            10000
          );
        } else {
          showNotification(
            result.message || "Failed to save schedule.",
            "error"
          );
        }
      }
    })
    .catch((error) => {
      console.error("Error saving schedule:", error);
      showNotification("Error saving schedule: " + error.message, "error");
      resetConflictStyles();
    })
    .finally(() => {
      // Restore button state
      submitButton.innerHTML = originalText;
      submitButton.disabled = false;
    });
}

// Conflict styling functions
function highlightConflictField(fieldId, message) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.classList.add("border-red-500", "bg-red-50");
    field.classList.remove("border-gray-300", "bg-white");

    // Add or update tooltip
    let tooltip = field.parentNode.querySelector(".conflict-tooltip");
    if (!tooltip) {
      tooltip = document.createElement("div");
      tooltip.className = "conflict-tooltip text-red-600 text-xs mt-1";
      field.parentNode.appendChild(tooltip);
    }
    tooltip.textContent = message;
  }
}

function resetConflictStyles() {
  // Reset all form fields
  const form = document.getElementById("schedule-form");
  const fields = form.querySelectorAll("input, select, textarea");

  fields.forEach((field) => {
    field.classList.remove("border-red-500", "bg-red-50");
    field.classList.add("border-gray-300");

    if (field.type !== "hidden") {
      field.style.backgroundColor = "";
    }
  });

  // Remove conflict tooltips
  const tooltips = form.querySelectorAll(".conflict-tooltip");
  tooltips.forEach((tooltip) => tooltip.remove());
}

// Enhanced showNotification function with longer duration support
function showNotification(message, type = "success", duration = 5000) {
  // Remove existing notification
  const existingNotification = document.getElementById("notification");
  if (existingNotification) {
    existingNotification.remove();
  }

  const notificationDiv = document.createElement("div");
  notificationDiv.id = "notification";
  notificationDiv.className = `fixed top-4 right-4 z-50 max-w-sm w-full ${
    type === "success"
      ? "bg-green-50 border border-green-200"
      : type === "error"
      ? "bg-red-50 border border-red-200"
      : "bg-yellow-50 border border-yellow-200"
  } rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full`;

  notificationDiv.innerHTML = `
        <div class="flex p-4">
            <div class="flex-shrink-0">
                <i class="fas ${
                  type === "success"
                    ? "fa-check-circle text-green-400"
                    : type === "error"
                    ? "fa-exclamation-circle text-red-400"
                    : "fa-exclamation-triangle text-yellow-400"
                } text-lg"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium ${
                  type === "success"
                    ? "text-green-800"
                    : type === "error"
                    ? "text-red-800"
                    : "text-yellow-800"
                } whitespace-pre-line">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button class="inline-flex ${
                  type === "success"
                    ? "text-green-400 hover:text-green-600"
                    : type === "error"
                    ? "text-red-400 hover:text-red-600"
                    : "text-yellow-400 hover:text-yellow-600"
                }" onclick="this.parentElement.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

  document.body.appendChild(notificationDiv);

  // Animate in
  setTimeout(() => {
    notificationDiv.classList.remove("translate-x-full");
    notificationDiv.classList.add("translate-x-0");
  }, 100);

  // Auto remove after duration
  setTimeout(() => {
    if (notificationDiv.parentElement) {
      notificationDiv.classList.add("translate-x-full");
      setTimeout(() => {
        if (notificationDiv.parentElement) {
          notificationDiv.remove();
        }
      }, 300);
    }
  }, duration);
}

// Initialize the enhanced modal functionality
document.addEventListener("DOMContentLoaded", function () {
  // Set up time field synchronization
  updateTimeFields();
  updateDayField();

  // Add event listeners for real-time updates
  const startTimeSelect = document.getElementById("start-time");
  const endTimeSelect = document.getElementById("end-time");
  const daySelect = document.getElementById("day-select");

  if (startTimeSelect)
    startTimeSelect.addEventListener("change", updateTimeFields);
  if (endTimeSelect) endTimeSelect.addEventListener("change", updateTimeFields);
  if (daySelect) daySelect.addEventListener("change", updateDayField);

  console.log("Enhanced manual schedules initialized");
});

// Enhanced function to build course mappings
function buildCurrentSemesterCourseMappings() {
  currentSemesterCourses = {};

  console.log(
    "Building course mappings for current semester:",
    window.currentSemester
  );
  console.log("Available curriculum courses:", window.curriculumCourses);

  // Use curriculum courses from window.curriculumCourses (now populated from PHP)
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

    // Also update the datalist with curriculum courses
    updateCourseCodesDatalist();
  } else {
    console.warn(
      "No curriculum courses found! Check if curriculum is set up correctly."
    );

    // Fallback: Get unique courses from current semester schedules
    const currentSemesterId = window.currentSemester?.semester_id;

    if (currentSemesterId) {
      const currentSemesterSchedules = window.scheduleData.filter(
        (schedule) => {
          return schedule.semester_id == currentSemesterId;
        }
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

// Function to update the course codes datalist
function updateCourseCodesDatalist() {
  const courseCodesDatalist = document.getElementById("course-codes");
  if (!courseCodesDatalist) return;

  // Clear existing options
  courseCodesDatalist.innerHTML = "";

  // Add curriculum courses to datalist
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

function filterSectionsByYearLevel() {
  const sectionSelect = document.getElementById("section-name");
  const courseCodeInput = document.getElementById("course-code");

  if (!sectionSelect || !courseCodeInput) return;

  const courseCode = courseCodeInput.value.trim().toUpperCase();

  // If no course code is entered, show all sections
  if (!courseCode) {
    resetSectionFilter();
    return;
  }

  // Get the course from our current semester courses
  const course = currentSemesterCourses[courseCode];
  if (!course || !course.year_level) {
    resetSectionFilter();
    showNotification(
      `No year level information found for course ${courseCode}. Showing all sections.`,
      "warning",
      3000
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

  // Show all options first
  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach((option) => {
    option.style.display = "";
  });

  // Hide options that don't match the year level
  const searchYear = targetYearLevel.toLowerCase().replace(" year", "").trim();
  let foundMatchingSections = false;

  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach((optgroup) => {
    const groupLabel = optgroup.label.toLowerCase();
    const groupYearLevel = groupLabel.replace(" year", "").trim();

    // Show matching year level groups
    if (groupYearLevel === searchYear) {
      const options = optgroup.querySelectorAll("option");
      options.forEach((option) => {
        option.style.display = "";
      });
      optgroup.style.display = "";
      foundMatchingSections = true;
    } else {
      // Hide non-matching year level groups
      const options = optgroup.querySelectorAll("option");
      options.forEach((option) => {
        option.style.display = "none";
      });
      optgroup.style.display = "none";
    }
  });

  // Reset selection if current selection is hidden
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
  if (selectedOption && selectedOption.style.display === "none") {
    sectionSelect.value = "";
  }

  // Show appropriate message
  if (foundMatchingSections) {
    showNotification(
      `Showing ${targetYearLevel} sections compatible with ${courseCode}`,
      "info",
      3000
    );
  } else {
    console.warn("No sections found for year level:", targetYearLevel);
    showNotification(
      `No ${targetYearLevel} sections found for ${courseCode}. Please check section availability.`,
      "warning",
      5000
    );
    // Fallback: show all sections if none match
    resetSectionFilter();
  }
}

// Enhanced reset function
function resetSectionFilter() {
  const sectionSelect = document.getElementById("section-name");
  if (!sectionSelect) return;

  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach((option) => {
    option.style.display = "";
  });

  // Also show all optgroups
  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach((optgroup) => {
    optgroup.style.display = "";
  });
}

function handleSectionChange() {
  const sectionSelect = document.getElementById("section-name");
  const courseCodeInput = document.getElementById("course-code");
  const courseNameInput = document.getElementById("course-name");
  const roomSelect = document.getElementById("room-name");

  if (!sectionSelect) return;

  const selectedSection = sectionSelect.value;
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];

  console.log("Section changed to:", selectedSection);

  // Logic 1: Auto-detect room capacity requirements
  if (selectedOption && roomSelect) {
    const sectionCapacity = extractCapacityFromSection(selectedOption.text);
    if (sectionCapacity) {
      highlightSuitableRooms(sectionCapacity);
    }
  }

  // Logic 2: Validate course-section compatibility
  if (courseCodeInput && courseCodeInput.value) {
    validateCourseSectionCompatibility(courseCodeInput.value, selectedSection);
  }

  // Logic 3: Check for existing schedules for this section
  if (selectedSection) {
    checkExistingSectionSchedules(selectedSection);
  }

  // Logic 4: Update section details display
  updateSectionDetails(selectedOption);
}

// Helper function to extract capacity from section text
function extractCapacityFromSection(sectionText) {
  // Extract capacity from format like "Section A (25/30)"
  const match = sectionText.match(/\((\d+)\/(\d+)\)/);
  if (match) {
    return parseInt(match[2]); // Max capacity
  }
  return null;
}

// Logic 1: Highlight suitable rooms based on section capacity
function highlightSuitableRooms(requiredCapacity) {
  const roomSelect = document.getElementById("room-name");
  if (!roomSelect) return;

  const options = roomSelect.querySelectorAll("option");

  options.forEach((option) => {
    if (option.value === "Online") {
      option.style.backgroundColor = "#f0f9ff"; // Light blue for online
      return;
    }

    // Extract room capacity from option text or data attribute
    const roomCapacity = extractRoomCapacity(option.text);
    if (roomCapacity && roomCapacity >= requiredCapacity) {
      option.style.backgroundColor = "#f0fff4"; // Light green for suitable
      option.title = `Capacity: ${roomCapacity} (Meets requirement: ${requiredCapacity})`;
    } else if (roomCapacity) {
      option.style.backgroundColor = "#fff0f0"; // Light red for insufficient
      option.title = `Capacity: ${roomCapacity} (Required: ${requiredCapacity})`;
    } else {
      option.style.backgroundColor = ""; // Default
      option.title = "";
    }
  });

  console.log("Highlighted rooms for capacity requirement:", requiredCapacity);
}

// Helper to extract room capacity
function extractRoomCapacity(roomText) {
  // Look for capacity in room name like "Room 101 (40)" or "Lab 201 (30)"
  const match = roomText.match(/\((\d+)\)/);
  return match ? parseInt(match[1]) : null;
}

// Logic 2: Validate course-section compatibility
function validateCourseSectionCompatibility(courseCode, sectionName) {
  const course = currentSemesterCourses[courseCode.toUpperCase()];
  if (!course || !course.year_level) return;

  const sectionSelect = document.getElementById("section-name");
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];

  if (!selectedOption) return;

  const sectionYearLevel = selectedOption.getAttribute("data-year-level");

  if (sectionYearLevel && course.year_level !== sectionYearLevel) {
    console.warn(
      `Course-Section Mismatch: Course (${course.year_level}) ≠ Section (${sectionYearLevel})`
    );

    // Show warning notification
    showNotification(
      `Warning: Course ${courseCode} is for ${course.year_level} but section ${sectionName} is ${sectionYearLevel}`,
      "warning",
      5000
    );
  } else {
    console.log("Course-section compatibility: OK");
  }
}

// Logic 3: Check for existing schedules for this section
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
      `Section ${sectionName} has ${existingSchedules.length} existing schedules:`,
      existingSchedules
    );

    // Optional: Show schedule count
    const scheduleCount = existingSchedules.length;
    const courseCodes = [
      ...new Set(existingSchedules.map((s) => s.course_code)),
    ].join(", ");

    showNotification(
      `Section ${sectionName} already has ${scheduleCount} schedule(s) for: ${courseCodes}`,
      "info",
      4000
    );
  }
}

// Logic 4: Update section details display
function updateSectionDetails(selectedOption) {
  // Create or update a section details display
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

    detailsDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <span class="font-medium">Section Details:</span>
                <span class="text-blue-600">${
                  yearLevel || "Unknown Year"
                }</span>
            </div>
            <div class="text-gray-600 mt-1">${sectionText}</div>
        `;
    detailsDiv.style.display = "block";
  } else {
    detailsDiv.style.display = "none";
  }
}

// Logic 5: Auto-suggest faculty based on section and course
function suggestFacultyForSection(courseCode, sectionName) {
  const facultySelect = document.getElementById("faculty-name");
  if (!facultySelect) return;

  // This is a simplified example - you would need actual faculty assignment data
  console.log("Suggesting faculty for:", courseCode, sectionName);

  // You could implement logic here to:
  // 1. Check which faculty typically teach this course
  // 2. Check faculty availability for this section's year level
  // 3. Highlight preferred faculty options
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
  document.getElementById("room-name").value = "Online"; // Set default to Online
  document.getElementById("section-name").value = ""; // Clear section selection

  // Set visible dropdowns
  document.getElementById("day-select").value = "Monday";
  document.getElementById("start-time").value = "07:30";
  document.getElementById("end-time").value = "08:30";
  document.getElementById("schedule-type").value = "f2f";

  // Reset section filter to show all sections
  resetSectionFilter();

  // Hide section details
  const sectionDetails = document.getElementById("section-details");
  if (sectionDetails) {
    sectionDetails.style.display = "none";
  }

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

  // Validate time logic
  if (data.start_time >= data.end_time) {
    showNotification("End time must be after start time.", "error");
    return;
  }

  // Show loading state
  const submitButton = e.target.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;
  submitButton.innerHTML =
    '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
  submitButton.disabled = true;

  // Submit form to generateSchedulesAjax endpoint
  fetch("/chair/generate-schedules", {
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

        let message =
          result.message ||
          (currentEditingId
            ? "Schedule updated successfully!"
            : "Schedule added successfully!");

        // Handle multiple schedules for day patterns
        if (result.schedules && result.schedules.length > 1) {
          message = `Schedules added successfully for ${result.schedules.length} days!`;

          // Add all new schedules to local data
          result.schedules.forEach((schedule) => {
            window.scheduleData.push({
              ...schedule,
              semester_id: currentSemesterId,
            });
          });
        } else if (result.schedules && result.schedules.length === 1) {
          // Single schedule (update or add)
          if (currentEditingId) {
            const index = window.scheduleData.findIndex(
              (s) => s.schedule_id == currentEditingId
            );
            if (index !== -1) {
              window.scheduleData[index] = {
                ...window.scheduleData[index],
                ...result.schedules[0],
                semester_id: currentSemesterId,
              };
            }
          } else {
            window.scheduleData.push({
              ...result.schedules[0],
              semester_id: currentSemesterId,
            });
          }
        }

        if (result.partial_success) {
          message += ` (${result.failed_days} day(s) had conflicts)`;
        }

        showNotification(message, "success");

        // Refresh display
        safeUpdateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();

        // Rebuild course mappings with new data
        buildCurrentSemesterCourseMappings();
      } else {
        // Show conflicts in a user-friendly way
        if (result.conflicts && result.conflicts.length > 0) {
          const conflictDetails = result.conflicts.join("\n• ");
          showNotification(
            `Schedule conflicts detected:\n• ${conflictDetails}`,
            "error",
            10000 // Show for 10 seconds
          );
        } else {
          showNotification(
            result.message || "Failed to save schedule.",
            "error"
          );
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
        safeUpdateScheduleDisplay(window.scheduleData);
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
        safeUpdateScheduleDisplay([]);
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

// Enhanced filter function
function filterSchedulesManual() {
  const yearLevel = document.getElementById("filter-year-manual").value;
  const section = document.getElementById("filter-section-manual").value;
  const room = document.getElementById("filter-room-manual").value;

  console.log("Filtering by:", { yearLevel, section, room });

  const scheduleCards = document.querySelectorAll(
    "#schedule-grid .schedule-card"
  );
  const dropZones = document.querySelectorAll("#schedule-grid .drop-zone");

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
      // Don't hide the parent drop-zone, just the card
    }
  });

  // Show/hide add buttons based on visibility
  dropZones.forEach((zone) => {
    const card = zone.querySelector(".schedule-card");
    const addButton = zone.querySelector(
      'button[onclick*="openAddModalForSlot"]'
    );

    if (addButton) {
      if (card && card.style.display === "none") {
        addButton.style.display = "block";
      } else if (card && card.style.display === "block") {
        addButton.style.display = "none";
      } else {
        addButton.style.display = "block";
      }
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
document.addEventListener("DOMContentLoaded", function () {
  // Initialize drag-and-drop
  initializeDragAndDrop();

  // Build initial course mappings from current semester
  buildCurrentSemesterCourseMappings();

  // Add event listener for add schedule button
  const addScheduleBtn = document.getElementById("add-schedule-btn");
  if (addScheduleBtn) addScheduleBtn.addEventListener("click", openAddModal);

  // Add event listener for save changes button
  const saveChangesBtn = document.getElementById("save-changes-btn");
  if (saveChangesBtn) saveChangesBtn.addEventListener("click", saveAllChanges);

  // Modal click outside to close handlers
  const deleteAllModal = document.getElementById("delete-all-modal");
  if (deleteAllModal) {
    deleteAllModal.addEventListener("click", function (e) {
      if (e.target === deleteAllModal) {
        closeDeleteAllModal();
      }
    });
  }

  const deleteSingleModal = document.getElementById("delete-single-modal");
  if (deleteSingleModal) {
    deleteSingleModal.addEventListener("click", function (e) {
      if (e.target === deleteSingleModal) {
        closeDeleteSingleModal();
      }
    });
  }

  const sectionSelect = document.getElementById("section-name");
  if (sectionSelect) {
    sectionSelect.addEventListener("change", handleSectionChange);
  }

  // Add keyboard event for ESC key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeDeleteAllModal();
      closeDeleteSingleModal();
    }
  });

  // Add event listeners for filter dropdowns
  const filterYear = document.getElementById("filter-year-manual");
  if (filterYear) filterYear.addEventListener("change", filterSchedulesManual);

  const filterSection = document.getElementById("filter-section-manual");
  if (filterSection)
    filterSection.addEventListener("change", filterSchedulesManual);

  const filterRoom = document.getElementById("filter-room-manual");
  if (filterRoom) filterRoom.addEventListener("change", filterSchedulesManual);

  // Add event listeners for course code and name sync
  const courseCodeInput = document.getElementById("course-code");
  if (courseCodeInput)
    courseCodeInput.addEventListener("input", syncCourseName);

  const courseNameInput = document.getElementById("course-name");
  if (courseNameInput)
    courseNameInput.addEventListener("input", syncCourseCode);

  // Ensure scheduleData is always an array
  if (!Array.isArray(window.scheduleData)) {
    window.scheduleData = [];
  }

  // Build initial course mappings from current semester
  buildCurrentSemesterCourseMappings();

  // Initialize with safe display
  setTimeout(() => {
    safeUpdateScheduleDisplay(window.scheduleData);

    // Initialize drag and drop after a brief delay to ensure DOM is ready
    setTimeout(() => {
      initializeDragAndDrop();
    }, 100);
  }, 100);

  // Modal click outside to close
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  }

  console.log("Manual schedules initialized successfully");
  console.log(
    "Available courses for current semester:",
    Object.keys(currentSemesterCourses).length
  );
});
