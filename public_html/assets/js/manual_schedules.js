// ============================================
// CORE VARIABLES & STATE
// ============================================
let currentEditingId = null;
let currentSemesterCourses = {};
let validationTimeout;
let currentDeleteScheduleId = null;
const DEBOUNCE_DELAY = 300;

// Drag and Drop State
let draggedElement = null;
let originalPosition = null;
let dragGhost = null;
let isValidatingConflicts = false;
let validationCache = new Map();
let lastValidationTime = 0;
const VALIDATION_THROTTLE = 500;

// ============================================
// DRAG AND DROP HANDLERS
// ============================================
function handleDragStart(e) {
  draggedElement = e.target.closest(".schedule-card");
  if (!draggedElement) return;

  originalPosition = {
    day: draggedElement.closest(".drop-zone").dataset.day,
    startTime: draggedElement.closest(".drop-zone").dataset.startTime,
    endTime: draggedElement.closest(".drop-zone").dataset.endTime,
    element: draggedElement,
  };

  e.dataTransfer.setData("text/plain", draggedElement.dataset.scheduleId);
  e.dataTransfer.effectAllowed = "move";

  draggedElement.classList.add("dragging");
  draggedElement.style.opacity = "0.5";
  draggedElement.style.transform = "scale(0.95)";
  createDragGhost(draggedElement);
}

function createDragGhost(element) {
  dragGhost = element.cloneNode(true);
  Object.assign(dragGhost.style, {
    position: "fixed",
    pointerEvents: "none",
    opacity: "0.8",
    zIndex: "9999",
    transform: "rotate(2deg)",
    boxShadow: "0 10px 25px rgba(0,0,0,0.3)"
  });
  document.body.appendChild(dragGhost);
}

function handleDragEnd(e) {
  if (draggedElement) {
    draggedElement.classList.remove("dragging");
    draggedElement.style.opacity = "";
    draggedElement.style.transform = "";
  }

  if (dragGhost && dragGhost.parentNode) dragGhost.remove();

  draggedElement = null;
  originalPosition = null;
  dragGhost = null;
  isValidatingConflicts = false;

  document.querySelectorAll(".drop-zone.drag-over, .drop-zone.conflict").forEach(zone => {
    zone.classList.remove("drag-over", "conflict", "conflict-high", "conflict-medium", "conflict-low");
  });
  document.querySelectorAll(".conflict-tooltip").forEach(tooltip => tooltip.remove());
}

function handleDragEnter(e) {
  const dropZone = e.target.closest(".drop-zone");
  if (dropZone && draggedElement) {
    dropZone.classList.add("drag-over");
    throttledConflictCheck(dropZone);
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
  const dropZone = e.target.closest(".drop-zone");
  if (dropZone && !dropZone.contains(e.relatedTarget)) {
    dropZone.classList.remove("drag-over");
    removeConflictTooltip(dropZone);
  }
}

async function handleDrop(e) {
  e.preventDefault();
  const dropZone = e.target.closest(".drop-zone");

  if (!dropZone || !draggedElement) return;

  dropZone.classList.remove("drag-over", "conflict");

  const scheduleId = e.dataTransfer.getData("text/plain");
  const newDay = dropZone.dataset.day;
  const newStartTime = dropZone.dataset.startTime;
  const newEndTime = dropZone.dataset.endTime;

  const cacheKey = `${scheduleId}-${newDay}-${newStartTime}-${newEndTime}`;

  if (validationCache.has(cacheKey)) {
    const cachedResult = validationCache.get(cacheKey);
    if (cachedResult.length > 0) {
      showDropConflicts(cachedResult);
      revertDrag();
      return;
    }
  }

  showLoadingIndicator(dropZone);
  const conflicts = await checkScheduleConflictsOptimized(scheduleId, newDay, newStartTime, newEndTime);
  hideLoadingIndicator(dropZone);

  validationCache.set(cacheKey, conflicts);
  if (validationCache.size > 50) {
    const firstKey = validationCache.keys().next().value;
    validationCache.delete(firstKey);
  }

  if (conflicts.length > 0) {
    showDropConflicts(conflicts);
    revertDrag();
    return;
  }

  performScheduleMove(scheduleId, newDay, newStartTime, newEndTime, dropZone);
}

// ============================================
// CONFLICT DETECTION
// ============================================
function throttledConflictCheck(dropZone) {
  const now = Date.now();
  if (now - lastValidationTime < VALIDATION_THROTTLE || isValidatingConflicts) return;
  checkDropZoneConflicts(dropZone);
}

async function checkScheduleConflictsOptimized(scheduleId, newDay, newStartTime, newEndTime) {
  const currentSchedule = window.scheduleData.find(s => s.schedule_id == scheduleId);
  if (!currentSchedule) return [];

  const clientConflicts = checkClientSideConflicts(currentSchedule, newDay, newStartTime + ":00", newEndTime + ":00");
  if (clientConflicts.length > 0) return clientConflicts;

  return new Promise((resolve) => {
    const formData = new URLSearchParams({
      action: "check_drag_conflicts",
      schedule_id: scheduleId,
      section_id: currentSchedule.section_id || "",
      faculty_id: currentSchedule.faculty_id || "",
      room_id: currentSchedule.room_id || "",
      day_of_week: newDay,
      start_time: newStartTime + ":00",
      end_time: newEndTime + ":00",
      semester_id: window.currentSemester?.semester_id || "",
    });

    fetch("/chair/generate-schedules", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData,
    })
      .then(response => response.json())
      .then(result => resolve(result.success && result.conflicts ? result.conflicts : []))
      .catch(error => {
        console.error("Conflict check error:", error);
        resolve([]);
      });
  });
}

function checkClientSideConflicts(currentSchedule, newDay, newStartTime, newEndTime) {
  const conflicts = [];
  const currentSemesterId = window.currentSemester?.semester_id;
  if (!currentSemesterId) return conflicts;

  const toMinutes = (timeStr) => {
    const [h, m] = timeStr.split(":").map(Number);
    return h * 60 + m;
  };

  const newStart = toMinutes(newStartTime);
  const newEnd = toMinutes(newEndTime);

  window.scheduleData.forEach((schedule) => {
    if (schedule.schedule_id == currentSchedule.schedule_id) return;
    if (schedule.semester_id != currentSemesterId) return;
    if (schedule.day_of_week !== newDay) return;

    const schedStart = toMinutes(schedule.start_time);
    const schedEnd = toMinutes(schedule.end_time);
    const hasOverlap = newStart < schedEnd && newEnd > schedStart;

    if (!hasOverlap) return;

    const sameSection = schedule.section_name === currentSchedule.section_name;
    const sameFaculty = schedule.faculty_name === currentSchedule.faculty_name;
    const sameRoom = schedule.room_name === currentSchedule.room_name;

    if (sameSection) {
      conflicts.push({
        type: "section",
        severity: "high",
        message: `Section ${schedule.section_name} already has ${schedule.course_code} at this time`,
      });
    }
    if (sameFaculty) {
      conflicts.push({
        type: "faculty",
        severity: "high",
        message: `Faculty ${schedule.faculty_name} is teaching ${schedule.course_code} at this time`,
      });
    }
    if (sameRoom && schedule.room_name !== "Online") {
      conflicts.push({
        type: "room",
        severity: "medium",
        message: `Room ${schedule.room_name} is occupied by ${schedule.course_code} at this time`,
      });
    }
  });

  return conflicts;
}

// ============================================
// UI HELPERS
// ============================================
function showLoadingIndicator(dropZone) {
  const indicator = document.createElement("div");
  indicator.className = "drop-loading-indicator";
  indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-yellow-500"></i>';
  indicator.style.cssText = "position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10;";
  dropZone.appendChild(indicator);
}

function hideLoadingIndicator(dropZone) {
  const indicator = dropZone.querySelector(".drop-loading-indicator");
  if (indicator) indicator.remove();
}

function showDropZoneConflictTooltip(dropZone, conflicts) {
  removeConflictTooltip(dropZone);

  const tooltip = document.createElement("div");
  tooltip.className = "conflict-tooltip";

  const highConflicts = conflicts.filter(c => c.severity === "high");
  const mediumConflicts = conflicts.filter(c => c.severity === "medium");

  let tooltipContent = '<div class="font-semibold mb-1 text-xs">⚠️ Conflicts:</div>';

  if (highConflicts.length > 0) {
    tooltipContent += '<div class="text-red-700 text-xs">';
    highConflicts.slice(0, 2).forEach(conflict => {
      tooltipContent += `<div>• ${conflict.message}</div>`;
    });
    tooltipContent += "</div>";
  }

  if (mediumConflicts.length > 0) {
    tooltipContent += '<div class="text-orange-600 text-xs mt-1">';
    mediumConflicts.slice(0, 1).forEach(conflict => {
      tooltipContent += `<div>• ${conflict.message}</div>`;
    });
    tooltipContent += "</div>";
  }

  tooltip.innerHTML = tooltipContent;

  if (highConflicts.length > 0) {
    tooltip.classList.add("bg-red-100", "border-red-300", "text-red-800");
    dropZone.classList.add("conflict-high");
  } else if (mediumConflicts.length > 0) {
    tooltip.classList.add("bg-orange-100", "border-orange-300", "text-orange-800");
    dropZone.classList.add("conflict-medium");
  }

  tooltip.classList.add("border", "rounded", "p-2", "shadow-lg", "absolute", "bottom-full", "left-0", "mb-2", "z-50", "max-w-xs", "text-xs");
  dropZone.appendChild(tooltip);
}

function removeConflictTooltip(dropZone) {
  const existingTooltip = dropZone.querySelector(".conflict-tooltip");
  if (existingTooltip) existingTooltip.remove();
  dropZone.classList.remove("conflict", "conflict-high", "conflict-medium", "conflict-low");
}

function showDropConflicts(conflicts) {
  const conflictMessages = conflicts.map(c => c.message).join("\n• ");
  showNotification(`Cannot move schedule:\n• ${conflictMessages}`, "error", 5000);
}

function revertDrag() {
  if (originalPosition && originalPosition.element) {
    originalPosition.element.classList.remove("opacity-50");
  }
}

function performScheduleMove(scheduleId, newDay, newStartTime, newEndTime, dropZone) {
  const scheduleIndex = window.scheduleData.findIndex(s => s.schedule_id == scheduleId);
  if (scheduleIndex === -1) {
    showNotification("Error: Schedule not found", "error");
    return;
  }

  const originalSchedule = window.scheduleData[scheduleIndex];
  const originalStart = new Date(`2000-01-01 ${originalSchedule.start_time.substring(0, 5)}`);
  const originalEnd = new Date(`2000-01-01 ${originalSchedule.end_time.substring(0, 5)}`);
  const durationMs = originalEnd - originalStart;
  const newEnd = new Date(`2000-01-01 ${newStartTime}`);
  newEnd.setMinutes(newEnd.getMinutes() + durationMs / 60000);
  const formattedEndTime = newEnd.toTimeString().substring(0, 5);

  window.scheduleData[scheduleIndex].day_of_week = newDay;
  window.scheduleData[scheduleIndex].start_time = newStartTime + ":00";
  window.scheduleData[scheduleIndex].end_time = formattedEndTime + ":00";

  safeUpdateScheduleDisplay(window.scheduleData);
  saveScheduleMoveToServer(scheduleId, newDay, newStartTime, formattedEndTime);
  showNotification(`Schedule moved to ${newDay} ${newStartTime}-${formattedEndTime}`, "success");
}

function saveScheduleMoveToServer(scheduleId, newDay, newStartTime, newEndTime) {
  const formData = new URLSearchParams({
    action: "update_schedule_drag",
    schedule_id: scheduleId,
    day_of_week: newDay,
    start_time: newStartTime + ":00",
    end_time: newEndTime + ":00",
  });

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: formData,
  })
    .then(response => response.json())
    .then(result => {
      if (!result.success) {
        console.error("Failed to save schedule move:", result.message);
        showNotification("Warning: Schedule move not saved to server", "warning");
      }
    })
    .catch(error => {
      console.error("Error saving schedule move:", error);
      showNotification("Error: Failed to save schedule move", "error");
    });
}

async function checkDropZoneConflicts(dropZone) {
  if (!draggedElement || isValidatingConflicts) return;

  isValidatingConflicts = true;
  lastValidationTime = Date.now();

  const scheduleId = draggedElement.dataset.scheduleId;
  const newDay = dropZone.dataset.day;
  const newStartTime = dropZone.dataset.startTime;
  const newEndTime = dropZone.dataset.endTime;

  const conflicts = await checkScheduleConflictsOptimized(scheduleId, newDay, newStartTime, newEndTime);

  if (conflicts.length > 0) {
    dropZone.classList.add("conflict");
    showDropZoneConflictTooltip(dropZone, conflicts);
  } else {
    dropZone.classList.remove("conflict", "conflict-high", "conflict-medium", "conflict-low");
    removeConflictTooltip(dropZone);
  }

  isValidatingConflicts = false;
}

function initializeDragAndDrop() {
  document.querySelectorAll(".drop-zone").forEach((zone) => {
    const newZone = zone.cloneNode(true);
    zone.parentNode.replaceChild(newZone, zone);
  });

  document.querySelectorAll(".schedule-card.draggable").forEach((draggable) => {
    const newDraggable = draggable.cloneNode(true);
    draggable.parentNode.replaceChild(newDraggable, draggable);
  });

  document.querySelectorAll(".drop-zone").forEach((zone) => {
    zone.addEventListener("dragover", handleDragOver);
    zone.addEventListener("dragenter", handleDragEnter);
    zone.addEventListener("dragleave", handleDragLeave);
    zone.addEventListener("drop", handleDrop);
  });

  document.querySelectorAll(".schedule-card.draggable").forEach((draggable) => {
    draggable.addEventListener("dragstart", handleDragStart);
    draggable.addEventListener("dragend", handleDragEnd);
  });
}

// ============================================
// MODAL & FORM HANDLING
// ============================================
function openAddModal() {
  buildCurrentSemesterCourseMappings();

  const form = document.getElementById("schedule-form");
  if (form) form.reset();

  resetConflictField("course-code");
  resetConflictField("course-name");

  document.getElementById("modal-title").textContent = "Add Schedule";
  document.getElementById("schedule-id").value = "";
  document.getElementById("modal-day").value = "Monday";
  document.getElementById("day-select").value = "Monday";
  document.getElementById("start-time").value = "07:30";
  document.getElementById("end-time").value = "08:30";
  document.getElementById("room-name").value = "Online";
  document.getElementById("schedule-type").value = "f2f";

  // Populate faculty dropdown
  const facultySelect = document.getElementById("faculty-name");
  if (facultySelect) {
    facultySelect.innerHTML = '<option value="">Select Faculty</option>';
    if (window.faculty && window.faculty.length > 0) {
      window.faculty.forEach((fac) => {
        const option = document.createElement("option");
        option.value = fac.name;
        option.textContent = fac.name;
        facultySelect.appendChild(option);
      });
    }
  }

  resetSectionFilter();
  currentEditingId = null;
  showModal();
}

function openAddModalForSlot(day, startTime, endTime) {
  openAddModal();
  document.getElementById("modal-day").value = day;
  document.getElementById("day-select").value = day;
  document.getElementById("start-time").value = startTime;
  updateEndTimeOptions();
  document.getElementById("end-time").value = endTime;
  updateTimeFields();
}

function editSchedule(scheduleId) {
  const schedule = window.scheduleData.find(s => s.schedule_id == scheduleId);
  if (!schedule) {
    showNotification("Schedule not found", "error");
    return;
  }

  resetConflictField("course-code");
  resetConflictField("course-name");

  if (schedule.semester_id != window.currentSemester?.semester_id) {
    showNotification("Can only edit schedules from current semester", "error");
    return;
  }

  buildCurrentSemesterCourseMappings();

  document.getElementById("modal-title").textContent = "Edit Schedule";
  document.getElementById("schedule-id").value = schedule.schedule_id;
  document.getElementById("course-code").value = schedule.course_code || "";
  document.getElementById("course-name").value = schedule.course_name || "";

  // Populate and set dropdowns
  const facultySelect = document.getElementById("faculty-name");
  const roomSelect = document.getElementById("room-name");
  const sectionSelect = document.getElementById("section-name");

  if (facultySelect) {
    if (facultySelect.options.length <= 1 && window.faculty) {
      facultySelect.innerHTML = '<option value="">Select Faculty</option>';
      window.faculty.forEach(fac => {
        const option = document.createElement("option");
        option.value = fac.name;
        option.textContent = fac.name;
        facultySelect.appendChild(option);
      });
    }
    facultySelect.value = schedule.faculty_name || "";
  }

  if (roomSelect) {
    roomSelect.value = schedule.room_name || "Online";
  }

  if (sectionSelect) {
    sectionSelect.value = schedule.section_name || "";
  }

  const day = schedule.day_of_week || "Monday";
  document.getElementById("modal-day").value = day;
  document.getElementById("day-select").value = day;

  const startTime = schedule.start_time ? schedule.start_time.substring(0, 5) : "07:30";
  const endTime = schedule.end_time ? schedule.end_time.substring(0, 5) : "08:30";

  document.getElementById("start-time").value = startTime;
  updateEndTimeOptions();
  document.getElementById("end-time").value = endTime;
  updateTimeFields();

  document.getElementById("schedule-type").value = schedule.schedule_type || "f2f";

  window.currentEditingId = scheduleId;
  showModal();
}

function showModal() {
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
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
  resetConflictStyles();

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
  };

  const submitButton = e.target.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;
  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
  submitButton.disabled = true;

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data),
  })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        closeModal();
        resetConflictStyles();
        
        if (result.schedules && result.schedules.length > 0) {
          result.schedules.forEach(schedule => {
            if (currentEditingId) {
              const index = window.scheduleData.findIndex(s => s.schedule_id == currentEditingId);
              if (index !== -1) window.scheduleData[index] = { ...window.scheduleData[index], ...schedule };
            } else {
              window.scheduleData.push({ ...schedule, semester_id: currentSemesterId });
            }
          });
        }

        showNotification(result.message || "Schedule saved successfully!", "success");
        safeUpdateScheduleDisplay(window.scheduleData);
        initializeDragAndDrop();
        buildCurrentSemesterCourseMappings();
      } else {
        showNotification(result.message || "Failed to save schedule.", "error");
      }
    })
    .catch(error => {
      console.error("Error saving schedule:", error);
      showNotification("Error saving schedule: " + error.message, "error");
    })
    .finally(() => {
      submitButton.innerHTML = originalText;
      submitButton.disabled = false;
    });
}

// ============================================
// DELETE OPERATIONS
// ============================================
function deleteAllSchedules() {
  const modal = document.getElementById("delete-confirmation-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
  }
}

function closeDeleteModal() {
  const modal = document.getElementById("delete-confirmation-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

function confirmDeleteAllSchedules() {
  const loadingOverlay = document.getElementById("loading-overlay");
  if (loadingOverlay) loadingOverlay.classList.remove("hidden");

  closeDeleteModal();

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "delete_schedules",
      confirm: "true",
    }),
  })
    .then(response => response.json())
    .then(data => {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");

      if (data.success) {
        showNotification(`Successfully deleted ${data.deleted_count || 0} schedule(s)!`, "success");
        window.scheduleData = [];
        safeUpdateScheduleDisplay([]);
        buildCurrentSemesterCourseMappings();
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification("Error: " + (data.message || "Failed to delete all schedules"), "error");
      }
    })
    .catch(error => {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");
      console.error("Delete all error:", error);
      showNotification("Error deleting all schedules: " + error.message, "error");
    });
}

function openDeleteSingleModal(scheduleId, courseCode, sectionName, day, startTime, endTime) {
  if (!scheduleId || scheduleId === "null" || scheduleId === "undefined") {
    showNotification("Error: Could not identify schedule.", "error");
    return;
  }

  currentDeleteScheduleId = scheduleId;

  const detailsElement = document.getElementById("single-delete-details");
  if (detailsElement) {
    detailsElement.innerHTML = `
      <div class="text-sm">
        <div class="font-semibold">${escapeHtml(courseCode)} - ${escapeHtml(sectionName)}</div>
        <div class="text-gray-600 mt-1">${escapeHtml(day)} • ${escapeHtml(startTime)} to ${escapeHtml(endTime)}</div>
      </div>
    `;
  }

  const modal = document.getElementById("delete-single-modal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
  }
}

function closeDeleteSingleModal() {
  const modal = document.getElementById("delete-single-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
  currentDeleteScheduleId = null;
}

function confirmDeleteSingleSchedule() {
  if (!currentDeleteScheduleId) {
    showNotification("Error: No schedule selected for deletion.", "error");
    return;
  }

  const scheduleIdToDelete = parseInt(currentDeleteScheduleId);
  if (isNaN(scheduleIdToDelete)) {
    showNotification("Error: Invalid schedule ID format.", "error");
    return;
  }

  const loadingOverlay = document.getElementById("loading-overlay");
  if (loadingOverlay) loadingOverlay.classList.remove("hidden");

  closeDeleteSingleModal();

  const formData = new URLSearchParams();
  formData.append("action", "delete_schedule");
  formData.append("schedule_id", scheduleIdToDelete.toString());
  formData.append("confirm", "true");

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: formData,
  })
    .then(response => response.json())
    .then(data => {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");

      if (data.success) {
        showNotification("Schedule deleted successfully!", "success");
        window.scheduleData = window.scheduleData.filter(s => s.schedule_id != scheduleIdToDelete);
        safeUpdateScheduleDisplay(window.scheduleData);
        setTimeout(initializeDragAndDrop, 100);
        buildCurrentSemesterCourseMappings();
      } else {
        showNotification(data.message || "Failed to delete schedule", "error");
      }
    })
    .catch(error => {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");
      showNotification("Error: " + error.message, "error");
    })
    .finally(() => {
      currentDeleteScheduleId = null;
    });
}

// ============================================
// GRID DISPLAY & UPDATE
// ============================================
function safeUpdateScheduleDisplay(schedules) {
  if (!schedules || !Array.isArray(schedules)) schedules = [];
  
  window.scheduleData = schedules;

  const manualGrid = document.getElementById("schedule-grid");
  if (manualGrid) updateManualGrid(schedules);

  const viewGrid = document.getElementById("timetableGrid");
  if (viewGrid) updateViewGrid(schedules);
}

function generateDynamicTimeSlotsFromSchedules(schedules) {
  if (!schedules || schedules.length === 0) {
    const defaultSlots = [];
    for (let h = 7; h < 21; h++) {
      for (let m = 0; m < 60; m += 30) {
        const start = `${h.toString().padStart(2, "0")}:${m.toString().padStart(2, "0")}`;
        const endH = m === 30 ? h + 1 : h;
        const endM = m === 30 ? 0 : 30;
        const end = `${endH.toString().padStart(2, "0")}:${endM.toString().padStart(2, "0")}`;
        if (endH < 21) defaultSlots.push([start, end]);
      }
    }
    return defaultSlots;
  }

  const timePointsSet = new Set();
  schedules.forEach(schedule => {
    if (schedule.start_time) timePointsSet.add(schedule.start_time.substring(0, 5));
    if (schedule.end_time) timePointsSet.add(schedule.end_time.substring(0, 5));
  });

  const timePoints = Array.from(timePointsSet)
    .filter(Boolean)
    .map(tp => {
      const [h, m] = tp.split(":").map(x => parseInt(x, 10));
      return { raw: tp, minutes: h * 60 + (m || 0) };
    })
    .sort((a, b) => a.minutes - b.minutes);

  if (timePoints.length === 0) return generateDynamicTimeSlotsFromSchedules([]);

  const minTime = timePoints[0];
  const maxTime = timePoints[timePoints.length - 1];

  const slots = [];
  let currentMinutes = minTime.minutes;
  const endMinutes = maxTime.minutes;

  while (currentMinutes < endMinutes) {
    const nextMinutes = currentMinutes + 30;
    const startH = Math.floor(currentMinutes / 60);
    const startM = currentMinutes % 60;
    const start = `${startH.toString().padStart(2, "0")}:${startM.toString().padStart(2, "0")}`;
    const endH = Math.floor(nextMinutes / 60);
    const endM = nextMinutes % 60;
    const end = `${endH.toString().padStart(2, "0")}:${endM.toString().padStart(2, "0")}`;
    slots.push([start, end]);
    currentMinutes = nextMinutes;
  }

  return slots;
}

function createScheduleCardSpanning(schedule, rowSpan, isManual) {
  const colors = [
    "bg-blue-100 border-blue-300 text-blue-800",
    "bg-green-100 border-green-300 text-green-800",
    "bg-purple-100 border-purple-300 text-purple-800",
    "bg-orange-100 border-orange-300 text-orange-800",
    "bg-pink-100 border-pink-300 text-pink-800",
    "bg-teal-100 border-teal-300 text-teal-800",
    "bg-amber-100 border-amber-300 text-amber-800",
  ];

  const colorIndex = schedule.schedule_id
    ? schedule.schedule_id % colors.length
    : 0;
  const colorClass = colors[colorIndex];

  const card = document.createElement("div");
  card.className = `schedule-card ${colorClass} p-3 rounded-lg border-l-4 ${
    isManual ? "draggable cursor-move" : ""
  } text-xs shadow-sm`;

  // Calculate exact height based on row span
  // Each slot is 60px, multiply by rowSpan, then subtract small amount for proper fit
  const calculatedHeight = rowSpan * 60 - 6;

  card.style.height = `${calculatedHeight}px`;
  card.style.minHeight = `${calculatedHeight}px`;
  card.style.display = "flex";
  card.style.flexDirection = "column";
  card.style.justifyContent = "space-between";
  card.style.overflow = "hidden";

  if (isManual) {
    card.draggable = true;
    card.dataset.scheduleId = schedule.schedule_id || "";
    card.dataset.yearLevel = schedule.year_level || "";
    card.dataset.sectionName = schedule.section_name || "";
    card.dataset.roomName = schedule.room_name || "Online";
  }

  const startTime = schedule.start_time
    ? formatTime(schedule.start_time.substring(0, 5))
    : "";
  const endTime = schedule.end_time
    ? formatTime(schedule.end_time.substring(0, 5))
    : "";

  card.innerHTML = `
    <div>
      ${
        isManual
          ? `
        <div class="flex justify-between items-start mb-2">
          <div class="font-bold truncate flex-1 text-sm">
            ${escapeHtml(schedule.course_code) || ""}
          </div>
          <div class="flex space-x-1 flex-shrink-0 ml-2 no-print">
            <button onclick="editSchedule('${schedule.schedule_id || ""}')" 
                    class="text-yellow-600 hover:text-yellow-700 transition-colors">
              <i class="fas fa-edit"></i>
            </button>
            <button onclick="openDeleteSingleModal('${
              schedule.schedule_id || ""
            }', '${escapeHtml(schedule.course_code) || ""}', '${
              escapeHtml(schedule.section_name) || ""
            }', '${
              escapeHtml(schedule.day_of_week) || ""
            }', '${startTime}', '${endTime}')" 
                    class="text-red-600 hover:text-red-700 transition-colors">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `
          : `
        <div class="font-bold text-sm truncate">
          ${escapeHtml(schedule.course_code) || ""}
        </div>
      `
      }
      <div class="space-y-1">
        <div class="text-xs opacity-90 truncate">
          ${escapeHtml(schedule.section_name) || ""}
        </div>
        <div class="text-xs opacity-75 truncate">
          ${escapeHtml(schedule.faculty_name) || ""}
        </div>
        <div class="text-xs opacity-75 truncate">
          ${escapeHtml(schedule.room_name) || "Online"}
        </div>
      </div>
    </div>
    <div class="text-xs font-semibold border-t border-current border-opacity-20 pt-1 mt-1">
      ${startTime} - ${endTime}
    </div>
  `;

  if (isManual) {
    card.ondragstart = handleDragStart;
    card.ondragend = handleDragEnd;
  }

  return card;
}

// ============================================
// KEEP THE IMPROVED MANUAL GRID (NO CHANGES)
// ============================================
function updateManualGrid(schedules) {
  console.log("🔨 updateManualGrid - Schedules:", schedules.length);

  const manualGrid = document.getElementById("schedule-grid");
  if (!manualGrid) {
    console.error("❌ Manual grid element not found");
    return;
  }

  manualGrid.innerHTML = "";

  const timeSlots = generateDynamicTimeSlotsFromSchedules(schedules);
  const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

  const scheduleLookup = {};
  schedules.forEach(schedule => {
    const day = schedule.day_of_week;
    const start = schedule.start_time ? schedule.start_time.substring(0, 5) : "";
    
    if (!scheduleLookup[day]) scheduleLookup[day] = {};
    if (!scheduleLookup[day][start]) scheduleLookup[day][start] = [];
    
    scheduleLookup[day][start].push(schedule);
  });

  const occupiedCells = new Set();

  timeSlots.forEach((timeSlot, timeIndex) => {
    const [slotStart, slotEnd] = timeSlot;

    const row = document.createElement("div");
    row.className = "grid grid-cols-8 border-b border-gray-100";
    row.style.minHeight = "60px";

    // Check if ANY schedule starts at this time slot
    let hasScheduleStarting = false;
    let sampleSchedule = null;
    
    days.forEach(day => {
      if (scheduleLookup[day] && scheduleLookup[day][slotStart] && scheduleLookup[day][slotStart].length > 0) {
        hasScheduleStarting = true;
        if (!sampleSchedule) {
          sampleSchedule = scheduleLookup[day][slotStart][0];
        }
      }
    });

    // TIME CELL
    const timeCell = document.createElement("div");
    timeCell.className = "border-r border-gray-200 sticky left-0 z-10 flex items-center justify-center";
    timeCell.style.minHeight = "60px";
    
    if (hasScheduleStarting && sampleSchedule) {
      const schedEnd = sampleSchedule.end_time.substring(0, 5);
      timeCell.className += " bg-blue-50 border-l-4 border-blue-500";
      timeCell.innerHTML = `
        <div class="px-2 py-1 text-center">
          <div class="text-sm font-bold text-blue-900">${formatTime(slotStart)}</div>
          <div class="text-[9px] text-blue-600 font-medium">to</div>
          <div class="text-sm font-bold text-blue-900">${formatTime(schedEnd)}</div>
        </div>
      `;
    } else {
      timeCell.className += " bg-gray-50";
      timeCell.innerHTML = `<span class="text-[10px] text-gray-300">${formatTime(slotStart)}</span>`;
    }
    
    row.appendChild(timeCell);

    // DAY CELLS
    days.forEach((day) => {
      const cellKey = `${day}-${timeIndex}`;

      if (occupiedCells.has(cellKey)) {
        return;
      }

      const cell = document.createElement("div");
      cell.className = "border-r border-gray-200 last:border-r-0 relative drop-zone";
      cell.style.minHeight = "60px";
      cell.dataset.day = day;
      cell.dataset.startTime = slotStart;
      cell.dataset.endTime = slotEnd;

      const schedulesStartingHere = [];

      if (scheduleLookup[day] && scheduleLookup[day][slotStart]) {
        scheduleLookup[day][slotStart].forEach(schedule => {
          const schedStart = schedule.start_time.substring(0, 5);
          const schedEnd = schedule.end_time.substring(0, 5);

          const rowSpan = calculateScheduleRowSpan(schedStart, schedEnd);

          for (let i = 0; i < rowSpan; i++) {
            const occupyKey = `${day}-${timeIndex + i}`;
            occupiedCells.add(occupyKey);
          }

          schedulesStartingHere.push({
            schedule: schedule,
            rowSpan: rowSpan,
          });
        });
      }

      if (schedulesStartingHere.length === 0) {
        cell.className += " p-1";
        const addButton = document.createElement("button");
        addButton.innerHTML = '<i class="fas fa-plus text-xs"></i>';
        addButton.className = "w-full h-full text-gray-400 hover:text-gray-600 hover:bg-yellow-50 rounded-lg border-2 border-dashed border-gray-300 hover:border-yellow-400 transition-all duration-200 no-print flex items-center justify-center";
        addButton.style.minHeight = "54px";
        addButton.onclick = () => openAddModalForSlot(day, slotStart, slotEnd);
        cell.appendChild(addButton);
      } else {
        cell.className += " p-1";
        
        schedulesStartingHere.forEach(({ schedule, rowSpan }) => {
          const scheduleCard = createScheduleCardSpanning(schedule, rowSpan, true);
          cell.appendChild(scheduleCard);
        });
      }

      row.appendChild(cell);
    });

    manualGrid.appendChild(row);
  });

  console.log("✅ Manual grid updated with", timeSlots.length, "time slots");
  setTimeout(() => initializeDragAndDrop(), 100);
}

// ============================================
// KEEP THE IMPROVED VIEW GRID (NO CHANGES)
// ============================================
function updateViewGrid(schedules) {
  const viewGrid = document.getElementById("timetableGrid");
  if (!viewGrid) return;

  viewGrid.innerHTML = "";

  const timeSlots = generateDynamicTimeSlotsFromSchedules(schedules);
  const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

  const occupiedCells = new Set();
  const scheduleLookup = {};
  
  schedules.forEach(schedule => {
    const day = schedule.day_of_week;
    const start = schedule.start_time ? schedule.start_time.substring(0, 5) : "";
    
    if (!scheduleLookup[day]) scheduleLookup[day] = {};
    if (!scheduleLookup[day][start]) scheduleLookup[day][start] = [];
    
    scheduleLookup[day][start].push(schedule);
  });

  timeSlots.forEach((time, timeIndex) => {
    const row = document.createElement("div");
    row.className = "grid grid-cols-8 border-b border-gray-100";
    row.style.minHeight = "60px";

    let hasScheduleStarting = false;
    let sampleSchedule = null;
    
    days.forEach(day => {
      if (scheduleLookup[day] && scheduleLookup[day][time[0]] && scheduleLookup[day][time[0]].length > 0) {
        hasScheduleStarting = true;
        if (!sampleSchedule) {
          sampleSchedule = scheduleLookup[day][time[0]][0];
        }
      }
    });

    const timeCell = document.createElement("div");
    timeCell.className = "border-r border-gray-200 flex items-center justify-center";
    timeCell.style.minHeight = "60px";
    
    if (hasScheduleStarting && sampleSchedule) {
      const schedEnd = sampleSchedule.end_time.substring(0, 5);
      timeCell.className += " bg-blue-50 border-l-4 border-blue-500";
      timeCell.innerHTML = `
        <div class="px-2 py-1 text-center">
          <div class="text-sm font-bold text-blue-900">${formatTime(time[0])}</div>
          <div class="text-[9px] text-blue-600 font-medium">to</div>
          <div class="text-sm font-bold text-blue-900">${formatTime(schedEnd)}</div>
        </div>
      `;
    } else {
      timeCell.className += " bg-gray-50";
      timeCell.innerHTML = `<span class="text-[10px] text-gray-300">${formatTime(time[0])}</span>`;
    }
    row.appendChild(timeCell);

    days.forEach(day => {
      const cellKey = `${day}-${timeIndex}`;

      if (occupiedCells.has(cellKey)) {
        return;
      }

      const cell = document.createElement("div");
      cell.className = "border-r border-gray-200 last:border-r-0 relative schedule-cell p-1";
      cell.style.minHeight = "60px";
      cell.dataset.day = day;
      cell.dataset.startTime = time[0];
      cell.dataset.endTime = time[1];

      const daySchedules = scheduleLookup[day] && scheduleLookup[day][time[0]] ? scheduleLookup[day][time[0]] : [];

      if (daySchedules.length > 0) {
        daySchedules.forEach(schedule => {
          const start = schedule.start_time.substring(0, 5);
          const end = schedule.end_time.substring(0, 5);
          const rowSpan = calculateScheduleRowSpan(start, end);

          for (let i = 0; i < rowSpan; i++) {
            const occupyKey = `${day}-${timeIndex + i}`;
            occupiedCells.add(occupyKey);
          }

          const scheduleItem = createScheduleCardSpanning(schedule, rowSpan, false);
          cell.appendChild(scheduleItem);
        });
      }

      row.appendChild(cell);
    });

    viewGrid.appendChild(row);
  });

  console.log("✅ View grid updated");
}

function createScheduleCard(schedule, rowSpan, isManual) {
  const colors = [
    "bg-blue-100 border-blue-300 text-blue-800",
    "bg-green-100 border-green-300 text-green-800",
    "bg-purple-100 border-purple-300 text-purple-800",
    "bg-orange-100 border-orange-300 text-orange-800",
    "bg-pink-100 border-pink-300 text-pink-800",
    "bg-teal-100 border-teal-300 text-teal-800",
    "bg-amber-100 border-amber-300 text-amber-800",
  ];

  const colorIndex = schedule.schedule_id
    ? schedule.schedule_id % colors.length
    : 0;
  const colorClass = colors[colorIndex];

  const card = document.createElement("div");
  card.className = `schedule-card ${colorClass} p-2 rounded-lg border-l-4 ${
    isManual ? "draggable cursor-move" : ""
  } text-xs mb-1`;

  // Calculate minimum height based on row span (60px per 30-minute slot)
  const minHeight = rowSpan * 60;
  card.style.minHeight = `${minHeight}px`;
  card.style.height = `${minHeight}px`;
  card.style.display = "flex";
  card.style.flexDirection = "column";
  card.style.justifyContent = "space-between";

  if (isManual) {
    card.draggable = true;
    card.dataset.scheduleId = schedule.schedule_id || "";
    card.dataset.yearLevel = schedule.year_level || "";
    card.dataset.sectionName = schedule.section_name || "";
    card.dataset.roomName = schedule.room_name || "Online";
  }

  const startTime = schedule.start_time
    ? formatTime(schedule.start_time.substring(0, 5))
    : "";
  const endTime = schedule.end_time
    ? formatTime(schedule.end_time.substring(0, 5))
    : "";
  const duration = calculateDurationDisplay(
    schedule.start_time,
    schedule.end_time
  );

  card.innerHTML = `
    ${
      isManual
        ? `
      <div class="flex justify-between items-start mb-1">
        <div class="font-bold text-sm truncate flex-1">${
          escapeHtml(schedule.course_code) || ""
        }</div>
        <div class="flex space-x-1 flex-shrink-0 ml-1">
          <button onclick="editSchedule('${
            schedule.schedule_id || ""
          }')" class="text-yellow-600 hover:text-yellow-700 no-print">
            <i class="fas fa-edit text-xs"></i>
          </button>
          <button onclick="openDeleteSingleModal('${
            schedule.schedule_id || ""
          }', '${escapeHtml(schedule.course_code) || ""}', '${
            escapeHtml(schedule.section_name) || ""
          }', '${
            escapeHtml(schedule.day_of_week) || ""
          }', '${startTime}', '${endTime}')" class="text-red-600 hover:text-red-700 no-print">
            <i class="fas fa-trash text-xs"></i>
          </button>
        </div>
      </div>
    `
        : `
      <div class="font-bold text-sm truncate mb-1">${
        escapeHtml(schedule.course_code) || ""
      }</div>
    `
    }
    <div class="flex-1">
      <div class="text-xs opacity-90 truncate mb-1">
        <i class="fas fa-users mr-1"></i>${
          escapeHtml(schedule.section_name) || ""
        }
      </div>
      <div class="text-xs opacity-75 truncate mb-1">
        <i class="fas fa-chalkboard-teacher mr-1"></i>${
          escapeHtml(schedule.faculty_name) || ""
        }
      </div>
      <div class="text-xs opacity-75 truncate mb-1">
        <i class="fas fa-door-open mr-1"></i>${
          escapeHtml(schedule.room_name) || "Online"
        }
      </div>
    </div>
    <div class="border-t border-gray-300 pt-1 mt-1">
      <div class="text-xs font-bold text-center">
        ${startTime} - ${endTime}
      </div>
      <div class="text-[10px] text-center opacity-75">
        ${duration}
      </div>
    </div>
  `;

  if (isManual) {
    card.ondragstart = handleDragStart;
    card.ondragend = handleDragEnd;
  }

  return card;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatTime(timeString) {
  if (!timeString) return "";
  const [hours, minutes] = timeString.split(":");
  const date = new Date(2000, 0, 1, hours, minutes);
  return date.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", hour12: true });
}

function calculateDurationDisplay(startTime, endTime) {
  if (!startTime || !endTime) return "";

  const toMinutes = (timeStr) => {
    const parts = timeStr.split(":");
    return parseInt(parts[0]) * 60 + parseInt(parts[1]);
  };

  const startMin = toMinutes(startTime);
  const endMin = toMinutes(endTime);
  const durationMin = endMin - startMin;

  const hours = Math.floor(durationMin / 60);
  const minutes = durationMin % 60;

  if (hours === 0) return `${minutes} mins`;
  if (minutes === 0) return `${hours} hr${hours > 1 ? "s" : ""}`;
  return `${hours} hr${hours > 1 ? "s" : ""} ${minutes} mins`;
}

function calculateScheduleRowSpan(scheduleStart, scheduleEnd) {
  const toMinutes = (timeStr) => {
    const parts = timeStr.split(":");
    const h = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    return h * 60 + m;
  };

  const startMin = toMinutes(scheduleStart);
  const endMin = toMinutes(scheduleEnd);
  const durationMin = endMin - startMin;

  // Each row represents 30 minutes
  const rowSpan = Math.ceil(durationMin / 30);

  console.log(
    `Schedule ${scheduleStart}-${scheduleEnd}: ${durationMin} mins = ${rowSpan} rows`
  );

  return Math.max(1, rowSpan);
}

function calculateRowSpan(startTime, endTime) {
  const [startH, startM] = startTime.split(":").map(Number);
  const [endH, endM] = endTime.split(":").map(Number);
  const startMinutes = startH * 60 + startM;
  const endMinutes = endH * 60 + endM;
  const durationMinutes = endMinutes - startMinutes;
  return Math.max(1, durationMinutes / 30);
}

function escapeHtml(unsafe) {
  if (!unsafe) return "";
  return String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function showNotification(message, type = "success", duration = 5000) {
  const existingNotification = document.getElementById("notification");
  if (existingNotification) existingNotification.remove();

  const notificationDiv = document.createElement("div");
  notificationDiv.id = "notification";

  let notificationClass = "fixed top-4 right-4 z-50 max-w-sm w-full ";
  let iconClass = "";
  let textClass = "";

  if (type === "success") {
    notificationClass += "bg-green-50 border border-green-200";
    iconClass = "fa-check-circle text-green-400";
    textClass = "text-green-800";
  } else if (type === "error") {
    notificationClass += "bg-red-50 border border-red-200";
    iconClass = "fa-exclamation-circle text-red-400";
    textClass = "text-red-800";
  } else {
    notificationClass += "bg-yellow-50 border border-yellow-200";
    iconClass = "fa-exclamation-triangle text-yellow-400";
    textClass = "text-yellow-800";
  }

  notificationClass += " rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full";
  notificationDiv.className = notificationClass;

  notificationDiv.innerHTML = `
    <div class="flex p-4">
      <div class="flex-shrink-0">
        <i class="fas ${iconClass} text-lg"></i>
      </div>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium ${textClass} whitespace-pre-line">${message}</p>
      </div>
      <div class="ml-auto pl-3">
        <button class="inline-flex text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  `;

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

// ============================================
// COURSE & SECTION HANDLING
// ============================================
function buildCurrentSemesterCourseMappings() {
  currentSemesterCourses = {};
  
  if (window.curriculumCourses && window.curriculumCourses.length > 0) {
    window.curriculumCourses.forEach(course => {
      if (course.course_code && course.course_name) {
        currentSemesterCourses[course.course_code.trim().toUpperCase()] = {
          code: course.course_code,
          name: course.course_name,
          course_id: course.course_id,
          year_level: course.curriculum_year,
          semester: course.curriculum_semester,
          units: course.units,
        };
      }
    });
    updateCourseCodesDatalist();
  }
}

function updateCourseCodesDatalist() {
  const courseCodesDatalist = document.getElementById("course-codes");
  if (!courseCodesDatalist) return;

  courseCodesDatalist.innerHTML = "";
  Object.values(currentSemesterCourses).forEach(course => {
    const option = document.createElement("option");
    option.value = course.code;
    option.setAttribute("data-name", course.name);
    option.setAttribute("data-year-level", course.year_level || "");
    option.setAttribute("data-course-id", course.course_id || "");
    courseCodesDatalist.appendChild(option);
  });
}

function autoFillCourseName(courseCode) {
  const courseNameInput = document.getElementById("course-name");
  if (!courseCode || !courseNameInput) return;

  const enteredCode = courseCode.trim().toUpperCase();
  removeConflictWarning("course-code");
  removeConflictWarning("course-name");

  if (currentSemesterCourses[enteredCode]) {
    const course = currentSemesterCourses[enteredCode];
    courseNameInput.value = course.name;
    setTimeout(() => {
      filterSectionsByYearLevel();
      handleSectionChange();
    }, 100);
  } else {
    courseNameInput.value = "";
    resetSectionFilter();
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
    return;
  }

  const targetYearLevel = course.year_level;
  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach(option => option.style.display = "");

  const searchYear = targetYearLevel.toLowerCase().replace(" year", "").trim();
  let foundMatchingSections = false;

  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach(optgroup => {
    const groupLabel = optgroup.label.toLowerCase();
    const groupYearLevel = groupLabel.replace(" year", "").trim();

    if (groupYearLevel === searchYear) {
      const options = optgroup.querySelectorAll("option");
      options.forEach(option => option.style.display = "");
      optgroup.style.display = "";
      foundMatchingSections = true;
    } else {
      const options = optgroup.querySelectorAll("option");
      options.forEach(option => option.style.display = "none");
      optgroup.style.display = "none";
    }
  });

  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
  if (selectedOption && selectedOption.style.display === "none") {
    sectionSelect.value = "";
  }
}

function resetSectionFilter() {
  const sectionSelect = document.getElementById("section-name");
  if (!sectionSelect) return;

  const allOptions = sectionSelect.querySelectorAll("option");
  allOptions.forEach(option => option.style.display = "");

  const optgroups = sectionSelect.querySelectorAll("optgroup");
  optgroups.forEach(optgroup => optgroup.style.display = "");
}

function handleSectionChange() {
  const sectionSelect = document.getElementById("section-name");
  if (!sectionSelect) return;

  const selectedSection = sectionSelect.value;
  const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];

  removeConflictWarning("section-name");

  if (selectedOption) {
    updateSectionDetails(selectedOption);
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
    detailsDiv.innerHTML = `
      <div class="flex justify-between items-center">
        <span class="font-medium">Section Details:</span>
        <span class="text-blue-600">${yearLevel || "Unknown Year"}</span>
      </div>
      <div class="text-gray-600 mt-1">${sectionText}</div>
    `;
    detailsDiv.style.display = "block";
  } else {
    detailsDiv.style.display = "none";
  }
}

function updateEndTimeOptions() {
  const startTimeSelect = document.getElementById("start-time");
  const endTimeSelect = document.getElementById("end-time");
  const selectedStartTime = startTimeSelect.value;

  if (!selectedStartTime) {
    endTimeSelect.innerHTML = '<option value="">Select End Time</option>';
    return;
  }

  endTimeSelect.innerHTML = '<option value="">Select End Time</option>';

  const [startHour, startMinute] = selectedStartTime.split(":").map(Number);
  const startTotalMinutes = startHour * 60 + startMinute;

  for (let duration = 30; duration <= 240; duration += 30) {
    const endTotalMinutes = startTotalMinutes + duration;
    const endHour = Math.floor(endTotalMinutes / 60);
    const endMinute = endTotalMinutes % 60;

    if (endHour < 22 || (endHour === 22 && endMinute === 0)) {
      const endTimeValue = `${endHour.toString().padStart(2, "0")}:${endMinute.toString().padStart(2, "0")}`;
      const endTimeDisplay = formatTime(endTimeValue);
      const durationText = duration >= 60 ? `${duration / 60}hr` : `${duration}min`;

      const option = document.createElement("option");
      option.value = endTimeValue;
      option.textContent = `${endTimeDisplay} (${durationText})`;
      option.setAttribute("data-duration", duration);
      endTimeSelect.appendChild(option);
    }
  }

  const oneHourOption = endTimeSelect.querySelector('option[data-duration="60"]');
  if (oneHourOption) oneHourOption.selected = true;

  updateTimeFields();
}

function updateTimeFields() {
  const startTimeSelect = document.getElementById("start-time");
  const endTimeSelect = document.getElementById("end-time");
  const modalStartTime = document.getElementById("modal-start-time");
  const modalEndTime = document.getElementById("modal-end-time");

  if (startTimeSelect && modalStartTime) modalStartTime.value = startTimeSelect.value + ":00";
  if (endTimeSelect && modalEndTime) modalEndTime.value = endTimeSelect.value + ":00";
}

function updateDayField() {
  const daySelect = document.getElementById("day-select");
  const modalDay = document.getElementById("modal-day");
  if (daySelect && modalDay) modalDay.value = daySelect.value;
}

// ============================================
// FILTER & VIEW FUNCTIONS
// ============================================
function filterSchedulesManual() {
  const currentView = document.getElementById("grid-view").classList.contains("hidden") ? "list" : "grid";

  if (currentView === "list") {
    filterSchedulesListView();
  } else {
    const yearLevel = document.getElementById("filter-year-manual").value;
    const section = document.getElementById("filter-section-manual").value;
    const room = document.getElementById("filter-room-manual").value;

    const scheduleCards = document.querySelectorAll("#schedule-grid .schedule-card");
    const dropZones = document.querySelectorAll("#schedule-grid .drop-zone");

    scheduleCards.forEach(card => {
      const cardYearLevel = card.getAttribute("data-year-level");
      const cardSectionName = card.getAttribute("data-section-name");
      const cardRoomName = card.getAttribute("data-room-name");

      const matchesYear = !yearLevel || cardYearLevel === yearLevel;
      const matchesSection = !section || cardSectionName === section;
      const matchesRoom = !room || cardRoomName === room;

      card.style.display = (matchesYear && matchesSection && matchesRoom) ? "block" : "none";
    });

    dropZones.forEach(zone => {
      const card = zone.querySelector(".schedule-card");
      const addButton = zone.querySelector('button[onclick*="openAddModalForSlot"]');
      if (addButton) {
        addButton.style.display = (card && card.style.display === "none") || !card ? "block" : "none";
      }
    });
  }
}

function filterSchedulesListView() {
  const yearLevel = document.getElementById("filter-year-manual").value.toLowerCase().trim();
  const section = document.getElementById("filter-section-manual").value.toLowerCase().trim();
  const room = document.getElementById("filter-room-manual").value.toLowerCase().trim();

  const tableRows = document.querySelectorAll("#list-view tbody tr.schedule-row");
  let visibleCount = 0;

  tableRows.forEach(row => {
    const rowYearLevel = (row.getAttribute("data-year-level") || "").toLowerCase().trim();
    const rowSectionName = (row.getAttribute("data-section-name") || "").toLowerCase().trim();
    const rowRoomName = (row.getAttribute("data-room-name") || "").toLowerCase().trim();

    const matchesYear = !yearLevel || rowYearLevel === yearLevel;
    const matchesSection = !section || rowSectionName === section;
    const matchesRoom = !room || rowRoomName === room;

    if (matchesYear && matchesSection && matchesRoom) {
      row.style.display = "";
      visibleCount++;
    } else {
      row.style.display = "none";
    }
  });

  const tbody = document.querySelector("#list-view tbody");
  let noResultsRow = tbody.querySelector(".no-results-row");

  if (visibleCount === 0 && tableRows.length > 0) {
    if (!noResultsRow) {
      noResultsRow = document.createElement("tr");
      noResultsRow.className = "no-results-row";
      noResultsRow.innerHTML = `
        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
          <i class="fas fa-search text-3xl mb-2"></i>
          <p class="mt-2">No schedules match the selected filters</p>
          <button onclick="clearFiltersManual()" class="mt-2 px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
            Clear Filters
          </button>
        </td>
      `;
      tbody.appendChild(noResultsRow);
    }
  } else if (visibleCount > 0 && noResultsRow) {
    noResultsRow.remove();
  }
}

function clearFiltersManual() {
  document.getElementById("filter-year-manual").value = "";
  document.getElementById("filter-section-manual").value = "";
  document.getElementById("filter-room-manual").value = "";
  filterSchedulesManual();
}

function toggleViewMode() {
  const gridView = document.getElementById("grid-view");
  const listView = document.getElementById("list-view");
  const toggleBtn = document.getElementById("toggle-view-btn");

  if (gridView.classList.contains("hidden")) {
    gridView.classList.remove("hidden");
    listView.classList.add("hidden");
    toggleBtn.innerHTML = '<i class="fas fa-list mr-1"></i><span>List View</span>';
    filterSchedulesManual();
  } else {
    gridView.classList.add("hidden");
    listView.classList.remove("hidden");
    toggleBtn.innerHTML = '<i class="fas fa-th mr-1"></i><span>Grid View</span>';
    filterSchedulesListView();
  }
}

// ============================================
// VALIDATION HELPERS
// ============================================
function displayConflictWarning(fieldId, message, warningLevel = "warning") {
  const field = document.getElementById(fieldId);
  if (!field) return;

  removeConflictWarning(fieldId);

  if (warningLevel === "error") {
    field.classList.add("border-red-500", "bg-red-50");
  } else if (warningLevel === "success") {
    field.classList.add("border-green-500", "bg-green-50");
  } else {
    field.classList.add("border-yellow-500", "bg-yellow-50");
  }

  const warningDiv = document.createElement("div");
  warningDiv.className = `conflict-warning text-${warningLevel === "error" ? "red" : warningLevel === "success" ? "green" : "yellow"}-600 text-xs mt-1`;
  warningDiv.textContent = message;
  field.parentNode.appendChild(warningDiv);
}

function removeConflictWarning(fieldId) {
  const field = document.getElementById(fieldId);
  if (!field) return;

  const parent = field.parentNode;
  const existingWarning = parent.querySelector(".conflict-warning");
  if (existingWarning) existingWarning.remove();

  field.classList.remove("border-red-500", "bg-red-50", "border-yellow-500", "bg-yellow-50", "border-green-500", "bg-green-50", "border-gray-300");
  field.classList.add("border-gray-300");
}

function resetConflictField(fieldId) {
  removeConflictWarning(fieldId);
}

function resetConflictStyles() {
  const form = document.getElementById("schedule-form");
  if (!form) return;

  const fields = form.querySelectorAll("input, select, textarea");
  fields.forEach((field) => {
    const fieldId = field.id;
    removeConflictWarning(fieldId);
  });
}

// ============================================
// EVENT LISTENERS & INITIALIZATION
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  // Check faculty data
  if (!window.faculty || window.faculty.length === 0) {
    if (window.jsData && window.jsData.faculty) {
      window.faculty = window.jsData.faculty;
    }
  }

  buildCurrentSemesterCourseMappings();

  // Initial display if schedules exist
  if (window.scheduleData && window.scheduleData.length > 0) {
    requestAnimationFrame(() => {
      safeUpdateScheduleDisplay(window.scheduleData);
      setTimeout(() => initializeDragAndDrop(), 100);
    });
  }

  // Attach filter listeners
  const filters = [
    "filter-year-manual",
    "filter-section-manual",
    "filter-room-manual",
  ];
  filters.forEach((filterId) => {
    const filter = document.getElementById(filterId);
    if (filter) {
      filter.addEventListener("change", () => filterSchedulesManual());
    }
  });

  // Modal event listeners
  const modal = document.getElementById("schedule-modal");
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });
  }

  const deleteModal = document.getElementById("delete-confirmation-modal");
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) closeDeleteModal();
    });
  }

  const deleteSingleModal = document.getElementById("delete-single-modal");
  if (deleteSingleModal) {
    deleteSingleModal.addEventListener("click", function (e) {
      if (e.target === deleteSingleModal) closeDeleteSingleModal();
    });
  }

  // Form field listeners
  const courseCodeInput = document.getElementById("course-code");
  if (courseCodeInput) {
    courseCodeInput.addEventListener("input", function () {
      autoFillCourseName(this.value);
    });
  }

  const sectionSelect = document.getElementById("section-name");
  if (sectionSelect) {
    sectionSelect.addEventListener("change", handleSectionChange);
  }

  const startTimeSelect = document.getElementById("start-time");
  if (startTimeSelect) {
    startTimeSelect.addEventListener("change", () => {
      updateEndTimeOptions();
      updateTimeFields();
    });
  }

  const endTimeSelect = document.getElementById("end-time");
  if (endTimeSelect) {
    endTimeSelect.addEventListener("change", updateTimeFields);
  }

  const daySelect = document.getElementById("day-select");
  if (daySelect) {
    daySelect.addEventListener("change", updateDayField);
  }

  // ESC key to close modals
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeDeleteModal();
      closeDeleteSingleModal();
      closeModal();
    }
  });
});

// ============================================
// ENHANCED TAB SWITCHING
// ============================================
(function () {
  const originalSwitchTab = window.switchTab;

  window.switchTab = function (tabName) {
    if (typeof originalSwitchTab === "function") {
      originalSwitchTab(tabName);
    } else {
      // Fallback implementation
      document.querySelectorAll(".tab-button").forEach((btn) => {
        btn.classList.remove("bg-yellow-500", "text-white");
        btn.classList.add(
          "text-gray-700",
          "hover:text-gray-900",
          "hover:bg-gray-100"
        );
      });

      const targetTab = document.getElementById(`tab-${tabName}`);
      if (targetTab) {
        targetTab.classList.add("bg-yellow-500", "text-white");
        targetTab.classList.remove(
          "text-gray-700",
          "hover:text-gray-900",
          "hover:bg-gray-100"
        );
      }

      document.querySelectorAll(".tab-content").forEach((content) => {
        content.classList.add("hidden");
      });

      const targetContent = document.getElementById(`content-${tabName}`);
      if (targetContent) {
        targetContent.classList.remove("hidden");
      }

      const url = new URL(window.location);
      url.searchParams.set(
        "tab",
        tabName === "schedule" ? "schedule-list" : tabName
      );
      window.history.pushState({}, "", url);
    }

    // Force grid update after tab switch
    if (
      (tabName === "manual" || tabName === "schedule") &&
      window.scheduleData &&
      window.scheduleData.length > 0
    ) {
      requestAnimationFrame(() => {
        setTimeout(() => {
          if (tabName === "manual" && typeof updateManualGrid === "function") {
            updateManualGrid(window.scheduleData);
            setTimeout(() => {
              if (typeof initializeDragAndDrop === "function") {
                initializeDragAndDrop();
              }
            }, 100);
          } else if (
            tabName === "schedule" &&
            typeof updateViewGrid === "function"
          ) {
            updateViewGrid(window.scheduleData);
          }
        }, 100);
      });
    }
  };
})();

// ============================================
// GLOBAL EXPORTS
// ============================================
window.safeUpdateScheduleDisplay = safeUpdateScheduleDisplay;
window.handleScheduleSubmit = handleScheduleSubmit;
window.openAddModal = openAddModal;
window.openAddModalForSlot = openAddModalForSlot;
window.editSchedule = editSchedule;
window.closeModal = closeModal;
window.deleteAllSchedules = deleteAllSchedules;
window.confirmDeleteAllSchedules = confirmDeleteAllSchedules;
window.closeDeleteModal = closeDeleteModal;
window.openDeleteSingleModal = openDeleteSingleModal;
window.confirmDeleteSingleSchedule = confirmDeleteSingleSchedule;
window.closeDeleteSingleModal = closeDeleteSingleModal;
window.autoFillCourseName = autoFillCourseName;
window.filterSchedulesManual = filterSchedulesManual;
window.clearFiltersManual = clearFiltersManual;
window.toggleViewMode = toggleViewMode;
window.updateEndTimeOptions = updateEndTimeOptions;
window.updateTimeFields = updateTimeFields;
window.updateDayField = updateDayField;