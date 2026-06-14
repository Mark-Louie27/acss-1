// ============================================
// INITIALIZE DATA
// ============================================
function initializeScheduleData() {
  window.sectionsData = Array.isArray(window.rawSectionsData)
    ? window.rawSectionsData.map((s, index) => ({
        section_id: s.section_id ?? index + 1,
        section_name: s.section_name ?? "",
        year_level: s.year_level ?? "Unknown",
        academic_year: s.academic_year ?? window.currentAcademicYear,
        current_students: s.current_students ?? 0,
        max_students: s.max_students ?? 30,
        semester: s.semester ?? "",
        is_active: s.is_active ?? 1,
        curriculum_id: s.curriculum_id || null,
      }))
    : [];

  if (window.sectionsData.length === 0) {
    console.warn("No sections found for the current semester.");
  }

  window.curriculumCourses = Array.isArray(window.jsData?.curriculumCourses)
    ? window.jsData.curriculumCourses.map((c, index) => ({
        course_id: c.course_id ?? index + 1,
        course_code: c.course_code ?? "",
        course_name: c.course_name ?? "Unknown",
        year_level: c.curriculum_year ?? "Unknown",
        semester:
          c.curriculum_semester ?? window.currentSemester?.semester_name,
        subject_type: c.subject_type ?? "",
        units: c.units ?? 0,
        lecture_units: c.lecture_units ?? 0,
        lab_units: c.lab_units ?? 0,
        lecture_hours: c.lecture_hours ?? 0,
        lab_hours: c.lab_hours ?? 0,
      }))
    : [];
}

// ============================================
// TOAST & NOTIFICATION HELPERS
// ============================================
function getOrCreateToastContainer() {
  let c = document.getElementById("toast-container");
  if (!c) {
    c = document.createElement("div");
    c.id = "toast-container";
    c.className = "fixed top-4 right-4 z-50 space-y-2";
    document.body.appendChild(c);
  }
  return c;
}

function showValidationToast(errors) {
  const container = getOrCreateToastContainer();
  const toast = document.createElement("div");
  toast.className =
    "bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg max-w-sm w-full transition-opacity duration-300";
  toast.innerHTML = `
    <div class="flex items-start">
      <i class="fas fa-exclamation-circle text-red-500 text-xl flex-shrink-0"></i>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium text-red-800">Validation Error</p>
        <ul class="list-disc pl-5 text-sm text-red-700 mt-1">
          ${errors.map((e) => `<li>${escapeHtml(String(e))}</li>`).join("")}
        </ul>
      </div>
      <button class="ml-3 text-red-400 hover:text-red-600 flex-shrink-0"
              onclick="this.closest('.transition-opacity').remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add("opacity-0");
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

function showCompletionToast(type, title, messages) {
  const container = getOrCreateToastContainer();
  const isSuccess = type === "success";
  const color = isSuccess ? "green" : "yellow";
  const icon = isSuccess ? "fa-check-circle" : "fa-exclamation-triangle";

  const toast = document.createElement("div");
  toast.className = `bg-${color}-50 border border-${color}-200 rounded-lg p-4 shadow-lg max-w-sm w-full transition-opacity duration-300`;
  toast.innerHTML = `
    <div class="flex items-start">
      <i class="fas ${icon} text-${color}-500 text-xl flex-shrink-0"></i>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium text-${color}-800">${escapeHtml(title)}</p>
        <ul class="list-disc pl-5 text-sm text-${color}-700 mt-1">
          ${messages.map((m) => `<li>${escapeHtml(String(m))}</li>`).join("")}
        </ul>
      </div>
      <button class="ml-3 text-${color}-400 hover:text-${color}-600 flex-shrink-0"
              onclick="this.closest('.transition-opacity').remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add("opacity-0");
    setTimeout(() => toast.remove(), 300);
  }, 7000);
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

function highlightField(fieldId, hasError) {
  const field = document.getElementById(fieldId);
  if (!field) return;
  field.classList.toggle("border-red-500", hasError);
  field.classList.toggle("ring-2", hasError);
  field.classList.toggle("ring-red-500", hasError);
  field.classList.toggle("border-gray-300", !hasError);
}

function clearValidationErrors() {
  ["curriculum_id"].forEach((id) => highlightField(id, false));
}

// ============================================
// CURRICULUM COURSES
// ============================================
function updateCourses() {
  const curriculumId = document.getElementById("curriculum_id").value;
  const coursesList = document.getElementById("courses-list");
  if (!coursesList) return;

  if (!curriculumId) {
    coursesList.innerHTML =
      '<p class="text-sm text-gray-600">Please select a curriculum to view available courses.</p>';
    return;
  }

  coursesList.innerHTML =
    '<p class="text-sm text-gray-600">Loading courses...</p>';

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "get_curriculum_courses",
      curriculum_id: curriculumId,
      semester_id: window.currentSemester?.semester_id,
    }),
  })
    .then((r) => r.json())
    .then((data) => {
      window.curriculumCourses = data.courses || [];
      if (window.curriculumCourses.length === 0) {
        coursesList.innerHTML =
          '<p class="text-sm text-red-600">No courses found for the selected curriculum and semester.</p>';
      } else {
        coursesList.innerHTML = `
          <ul class="list-disc pl-5 text-sm text-gray-700">
            ${window.curriculumCourses
              .map(
                (c) => `
              <li>${escapeHtml(c.course_code)} - ${escapeHtml(c.course_name)}
                (Year: ${escapeHtml(c.curriculum_year)}, Sem: ${escapeHtml(c.curriculum_semester)})
              </li>`,
              )
              .join("")}
          </ul>`;
      }
      // rebuild datalist in modal
      if (typeof buildCurrentSemesterCourseMappings === "function") {
        buildCurrentSemesterCourseMappings();
      }
    })
    .catch((err) => {
      coursesList.innerHTML =
        '<p class="text-sm text-red-600">Error loading courses. Please try again.</p>';
      showValidationToast(["Error loading courses: " + err.message]);
    });
}

// ============================================
// REGENERATE MODAL
// ============================================
function checkExistingSchedulesBeforeGenerate() {
  if (window.scheduleData && window.scheduleData.length > 0) {
    showRegenerateModal();
    return true;
  }
  return false;
}

function showRegenerateModal() {
  const modal = document.getElementById("regenerate-confirmation-modal");
  if (!modal) return;
  updateRegenerateModalStats();
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeRegenerateModal() {
  const modal = document.getElementById("regenerate-confirmation-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

function updateRegenerateModalStats() {
  if (!window.scheduleData?.length) return;
  const set = (arr) => new Set(arr).size;
  const s = window.scheduleData;
  const el = (id) => document.getElementById(id);
  if (el("current-courses-count"))
    el("current-courses-count").textContent = set(s.map((x) => x.course_code));
  if (el("current-sections-count"))
    el("current-sections-count").textContent = set(
      s.map((x) => x.section_name),
    );
  if (el("current-faculty-count"))
    el("current-faculty-count").textContent = set(s.map((x) => x.faculty_name));
  if (el("current-rooms-count"))
    el("current-rooms-count").textContent = set(s.map((x) => x.room_name));
}

function confirmRegenerate() {
  closeRegenerateModal();
  const overlay = document.getElementById("loading-overlay");
  if (overlay) overlay.classList.remove("hidden");
  setTimeout(proceedWithGeneration, 300);
}

// ============================================
// MAIN GENERATE FUNCTION
// ============================================
function generateSchedules() {
  const form = document.getElementById("generate-form");
  if (!form) return;

  const curriculumId = new FormData(form).get("curriculum_id");
  clearValidationErrors();

  const errors = [];
  if (!curriculumId) {
    errors.push("Please select a curriculum");
    highlightField("curriculum_id", true);
  }
  if (!window.sectionsData?.length)
    errors.push("No sections available for the current semester");
  if (!window.curriculumCourses?.length)
    errors.push("No courses available for the selected curriculum");
  if (!window.faculty?.length) errors.push("No faculty members available");
  if (!window.classrooms?.length) errors.push("No classrooms available");

  if (errors.length) {
    showValidationToast(errors);
    return;
  }

  if (checkExistingSchedulesBeforeGenerate()) return; // modal handles the rest

  const overlay = document.getElementById("loading-overlay");
  if (overlay) overlay.classList.remove("hidden");
  setTimeout(proceedWithGeneration, 300);
}

function proceedWithGeneration() {
  const form = document.getElementById("generate-form");
  if (!form) {
    hideLoadingAndShowError(null, "Form not found");
    return;
  }

  const formData = new FormData(form);
  const payload = new URLSearchParams({
    action: "generate_schedule",
    curriculum_id: formData.get("curriculum_id"),
    semester_id: formData.get("semester_id"),
    force_regenerate: "true",
  });

  const startTime = performance.now();

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: payload,
  })
    .then((r) => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then((data) => {
      if (data.success) {
        updateUIAfterGeneration(
          data,
          document.getElementById("loading-overlay"),
          startTime,
        );
      } else {
        hideLoadingAndShowError(
          document.getElementById("loading-overlay"),
          data.message || "Failed to generate schedules",
        );
      }
    })
    .catch((err) =>
      hideLoadingAndShowError(
        document.getElementById("loading-overlay"),
        "Error: " + err.message,
      ),
    );
}

// ============================================
// POST-GENERATION UI UPDATE
// ============================================

/**
 * Detect whether the schedule data already has start_time / end_time fields
 * (fully structured, per-row format) vs the consolidated format
 * returned by getConsolidatedSchedules() (course_code + lecture/lab strings).
 */
function isStructuredSchedule(schedule) {
  return !!(schedule.start_time && schedule.end_time && schedule.day_of_week);
}

/**
 * Detect the "consolidated" format from getConsolidatedSchedules():
 * { course_code, instructor, sections, section_id, lecture, lab }
 */
function isConsolidatedSchedule(schedule) {
  return !!(
    schedule &&
    (schedule.lecture !== undefined || schedule.lab !== undefined)
  );
}

async function updateUIAfterGeneration(
  responseData,
  loadingOverlay,
  startTime,
) {
  try {
    if (
      !Array.isArray(responseData.schedules) ||
      responseData.schedules.length === 0
    ) {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");
      showValidationToast([
        "No schedules were generated. Check that sections, faculty, rooms and courses are configured.",
      ]);
      return;
    }

    let finalSchedules;

    // ── MVC backend returns structured rows directly ──────────────────────────
    if (isStructuredSchedule(responseData.schedules[0])) {
      finalSchedules = responseData.schedules;
      console.log(
        `✅ Using ${finalSchedules.length} structured schedule rows from backend`,
      );

      // ── getConsolidatedSchedules() format: { course_code, lecture, lab, sections, ... } ──
    } else if (isConsolidatedSchedule(responseData.schedules[0])) {
      finalSchedules = [];
      responseData.schedules.forEach((s, i) => {
        const transformed = transformConsolidatedSchedule(s, i);
        if (transformed) finalSchedules.push(...transformed);
      });
      console.log(
        `✅ Transformed ${finalSchedules.length} schedule entries from consolidated format`,
      );
    } else {
      // ── Legacy "time" string format — transform ────────────────────────────
      finalSchedules = [];
      responseData.schedules.forEach((s, i) => {
        const transformed = transformBackendSchedule(s, i);
        if (transformed) finalSchedules.push(...transformed);
      });
      console.log(
        `✅ Transformed ${finalSchedules.length} schedule entries from legacy format`,
      );
    }

    if (!finalSchedules.length) {
      throw new Error("Schedule transformation produced no results");
    }

    window.scheduleData = finalSchedules;

    if (loadingOverlay) loadingOverlay.classList.add("hidden");

    const elapsed = ((performance.now() - startTime) / 1000).toFixed(2);
    const uniqueSections = new Set(finalSchedules.map((s) => s.section_name))
      .size;

    showCompletionToast("success", "Schedules Generated!", [
      `${finalSchedules.length} entries across ${uniqueSections} sections`,
      responseData.unassignedCourses?.length
        ? `⚠️ ${responseData.unassignedCourses.length} courses need manual scheduling`
        : "All courses scheduled successfully!",
      `Completed in ${elapsed}s — refreshing page…`,
    ]);

    if (responseData.unassignedCourses?.length) {
      showIncompleteScheduleBanner(responseData);
    }

    // Reload to manual tab so PHP re-fetches fresh data from DB
    setTimeout(() => {
      const url = new URL(window.location);
      url.searchParams.set("tab", "manual");
      window.location.href = url.toString();
    }, 2500);
  } catch (err) {
    console.error("updateUIAfterGeneration error:", err);
    hideLoadingAndShowError(
      loadingOverlay,
      "Error displaying schedules: " + err.message,
    );
  }
}

// ============================================
// CONSOLIDATED FORMAT TRANSFORMER
// getConsolidatedSchedules() returns:
// { course_code, instructor, sections, section_id, lecture, lab }
// where lecture/lab are strings like:
//   "MWF 7:30-8:30 am Room 101; T 11:30 am-1:00 pm Online"
// ============================================
function transformConsolidatedSchedule(row, baseIndex) {
  try {
    const sections = (row.sections || "")
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean);
    const sectionList = sections.length ? sections : ["Section A"];

    const results = [];
    let counter = 0;

    ["lecture", "lab"].forEach((component) => {
      const str = row[component];
      if (!str) return;

      const groups = str
        .split(";")
        .map((g) => g.trim())
        .filter(Boolean);

      groups.forEach((group) => {
        // consolidateSlots() produces "<DAYS> <TIME-RANGE> <ROOM>" where TIME-RANGE is one of:
        //   "7:30-8:30 am"        (same period, lowercase, ONE suffix)
        //   "11:30 am-1:00 pm"    (different periods, lowercase, TWO suffixes)
        const m = group.match(
          /^([A-Za-z]+)\s+(\d{1,2}:\d{2})\s*(am|pm)?-(\d{1,2}:\d{2})\s*(am|pm)\s+(.+)$/i,
        );
        if (!m) {
          console.warn(
            "transformConsolidatedSchedule: could not parse group:",
            group,
          );
          return;
        }

        const dayCode = m[1];
        const startRaw = m[2];
        const startPer = (m[3] || m[5]).toLowerCase(); // fall back to end period if start has none
        const endRaw = m[4];
        const endPer = m[5].toLowerCase();
        const room = m[6].trim();

        const days = parseDays(dayCode);

        const startTime = convertTo24Hour(startRaw, startPer);
        const endTime = convertTo24Hour(endRaw, endPer);

        sectionList.forEach((sectionName) => {
          days.forEach((day) => {
            results.push({
              schedule_id: baseIndex * 10000 + counter++,
              course_code: row.course_code || "Unknown",
              course_name: row.course_name || row.course_code || "Unknown",
              faculty_name: row.instructor || "TBA",
              day_of_week: day,
              start_time: startTime + ":00",
              end_time: endTime + ":00",
              room_name: room.toLowerCase() === "online" ? "Online" : room,
              section_name: sectionName,
              section_id: row.section_id || null,
              year_level: getYearLevel(row),
              component_type: component,
              semester_id: window.currentSemester?.semester_id || null,
              department_id: window.departmentId || null,
              current_students: 0,
              max_students: 40,
            });
          });
        });
      });
    });

    return results;
  } catch (err) {
    console.error("transformConsolidatedSchedule error:", err, row);
    return null;
  }
}

function transformBackendSchedule(backendSchedule, baseIndex) {
  try {
    const timeRoomStr = backendSchedule.time || "";
    const parentDays = backendSchedule.days || "";
    if (!timeRoomStr) return null;

    const timeSlots = parseAllTimeSlots(
      timeRoomStr,
      backendSchedule.room || "",
    );
    if (!timeSlots.length) return null;

    const parentDaysList = parseDays(parentDays);
    const sections = getSectionsForSchedule(backendSchedule);
    const yearLevel = getYearLevel(backendSchedule);

    const results = [];
    let counter = 0;

    sections.forEach((sectionName) => {
      timeSlots.forEach((slot) => {
        const days = slot.days || parentDaysList;
        days.forEach((day) => {
          results.push({
            schedule_id: baseIndex * 1000 + counter++,
            course_code: backendSchedule.course_code || "Unknown",
            course_name:
              backendSchedule.course_name ||
              backendSchedule.course_code ||
              "Unknown",
            faculty_name:
              backendSchedule.instructor ||
              backendSchedule.faculty_name ||
              "TBA",
            day_of_week: day,
            start_time: slot.startTime + ":00",
            end_time: slot.endTime + ":00",
            room_name: slot.room,
            section_name: sectionName,
            year_level: yearLevel,
            semester_id: window.currentSemester?.semester_id || null,
            department_id: window.departmentId || null,
            lecture_units: backendSchedule.lec || 0,
            lab_units: backendSchedule.lab || 0,
            current_students: 0,
            max_students: 40,
          });
        });
      });
    });

    return results;
  } catch (err) {
    console.error("transformBackendSchedule error:", err, backendSchedule);
    return null;
  }
}

function parseAllTimeSlots(timeString, roomString) {
  const slots = [];
  timeString.split(";").forEach((segment) => {
    segment = segment.trim();
    if (!segment) return;

    let days = null;
    let rest = segment;
    const dayMatch = segment.match(/^([MTWFS]+|TTH|TH)\s+/i);
    if (dayMatch) {
      days = parseDays(dayMatch[1]);
      rest = segment.slice(dayMatch[0].length);
    }

    const tMatch = rest.match(
      /(\d{1,2}:\d{2})\s*(?:am|pm)?\s*-\s*(\d{1,2}:\d{2})\s*(am|pm)/i,
    );
    if (!tMatch) return;

    const endPeriod = tMatch[3].toLowerCase();
    const startPMatch = rest.match(new RegExp(tMatch[1] + "\\s*(am|pm)", "i"));
    const startPeriod = startPMatch ? startPMatch[1].toLowerCase() : endPeriod;

    const startTime = convertTo24Hour(tMatch[1], startPeriod);
    const endTime = convertTo24Hour(tMatch[2], endPeriod);

    let room = "Online";
    const rMatch = rest.match(/(Room|Laboratory|Lab)\s+\d+/i);
    if (rMatch) {
      room = rMatch[0];
    } else if (roomString && !roomString.toLowerCase().includes("online")) {
      room = roomString;
    }

    slots.push({ days, startTime, endTime, room });
  });
  return slots;
}

function convertTo24Hour(timeStr, period) {
  let [h, m] = timeStr.split(":").map(Number);
  if (period === "pm" && h !== 12) h += 12;
  if (period === "am" && h === 12) h = 0;
  return `${h.toString().padStart(2, "0")}:${m.toString().padStart(2, "0")}`;
}

function parseDays(dayCode) {
  if (!dayCode) return ["Monday"];
  dayCode = dayCode.toUpperCase().trim();
  const map = { MWF: "MWF", TTH: "TTH", TH: "TTH", MW: "MW", TF: "TF" };
  if (map[dayCode]) dayCode = map[dayCode];

  const expanded = {
    MWF: ["Monday", "Wednesday", "Friday"],
    TTH: ["Tuesday", "Thursday"],
    MW: ["Monday", "Wednesday"],
    TF: ["Tuesday", "Friday"],
    SAT: ["Saturday"],
    SUN: ["Sunday"],
  };
  if (expanded[dayCode]) return expanded[dayCode];

  const charMap = {
    M: "Monday",
    T: "Tuesday",
    W: "Wednesday",
    R: "Thursday",
    F: "Friday",
    S: "Saturday",
  };
  const days = [];
  for (let i = 0; i < dayCode.length; i++) {
    if (i < dayCode.length - 1 && dayCode.slice(i, i + 2) === "TH") {
      days.push("Thursday");
      i++;
      continue;
    }
    if (charMap[dayCode[i]]) days.push(charMap[dayCode[i]]);
  }
  return [...new Set(days.length ? days : ["Monday"])];
}

function getSectionsForSchedule(bs) {
  if (bs.section_name) return [bs.section_name];
  if (Array.isArray(bs.sections) && bs.sections.length) return bs.sections;
  if (bs.sections && typeof bs.sections === "string") return [bs.sections];
  const yl = bs.year_level || getYearLevel(bs);
  const matching =
    window.sectionsData
      ?.filter((s) => s.year_level === yl)
      .map((s) => s.section_name) || [];
  return matching.length ? matching : ["Section A"];
}

function getYearLevel(bs) {
  if (bs.year_level) return bs.year_level;
  const m = (bs.course_code || "").match(/(\d)0\d/);
  if (m) {
    const n = parseInt(m[1]);
    if (n >= 1 && n <= 4)
      return ["1st Year", "2nd Year", "3rd Year", "4th Year"][n - 1];
  }
  return "1st Year";
}

// ============================================
// INCOMPLETE SCHEDULE BANNER
// ============================================
function showIncompleteScheduleBanner(responseData) {
  const existing = document.getElementById("incomplete-schedule-banner");
  if (existing) existing.remove();
  if (!responseData.unassignedCourses?.length) return;

  const navTabs = document.querySelector("nav.flex.space-x-1");
  if (!navTabs?.parentElement) return;

  const banner = document.createElement("div");
  banner.id = "incomplete-schedule-banner";
  banner.className =
    "mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg shadow-sm";
  banner.innerHTML = `
    <div class="flex items-start">
      <i class="fas fa-exclamation-triangle text-yellow-600 text-xl flex-shrink-0"></i>
      <div class="ml-3 flex-1">
        <h3 class="text-sm font-semibold text-yellow-800">⚠️ ${responseData.unassignedCourses.length} course(s) could not be scheduled</h3>
        <div class="mt-2 text-sm text-yellow-700">
          <ul class="list-disc list-inside ml-2 max-h-32 overflow-y-auto">
            ${responseData.unassignedCourses
              .map(
                (c) =>
                  `<li><strong>${escapeHtml(c.course_code)}</strong> - ${escapeHtml(c.course_name || "")}</li>`,
              )
              .join("")}
          </ul>
          <p class="mt-2 text-xs">Success rate: ${responseData.successRate || "0%"}</p>
        </div>
        <div class="mt-3 flex space-x-2">
          <button onclick="window.switchTab('manual')"
                  class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded">
            <i class="fas fa-edit mr-1"></i>Manual Edit
          </button>
          <button onclick="window.tryRegenerateIncomplete()"
                  class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded">
            <i class="fas fa-sync-alt mr-1"></i>Try Regenerate
          </button>
        </div>
      </div>
      <button onclick="this.closest('#incomplete-schedule-banner').remove()"
              class="ml-3 text-yellow-600 hover:text-yellow-800 flex-shrink-0">
        <i class="fas fa-times"></i>
      </button>
    </div>`;
  navTabs.parentElement.insertBefore(banner, navTabs.nextSibling);
}

window.tryRegenerateIncomplete = function () {
  const form = document.getElementById("generate-form");
  if (!form) {
    showValidationToast(["Generate form not found"]);
    return;
  }
  const curriculumId = new FormData(form).get("curriculum_id");
  if (!curriculumId) {
    showValidationToast(["Please select a curriculum first"]);
    return;
  }
  const overlay = document.getElementById("loading-overlay");
  if (overlay) overlay.classList.remove("hidden");
  proceedWithGeneration();
};

function hideLoadingAndShowError(overlay, message) {
  if (overlay) overlay.classList.add("hidden");
  showValidationToast([message]);
}

// ============================================
// EXPORT HELPERS
// ============================================
function getFilteredSchedules() {
  const year = document.getElementById("filter-year")?.value || "";
  const section = document.getElementById("filter-section")?.value || "";
  const room = document.getElementById("filter-room")?.value || "";
  return (window.scheduleData || []).filter(
    (s) =>
      (!year || s.year_level === year) &&
      (!section || s.section_name === section) &&
      (!room || (s.room_name || "Online") === room),
  );
}

function updateScheduleCompletionStatus(data) {
  const existing = document.getElementById("schedule-completion-banner");
  if (existing) existing.remove();
  if (!data.unassignedCourses?.length) return;

  const navTabs = document.querySelector("nav.flex.space-x-1");
  if (!navTabs?.parentElement) return;

  const banner = document.createElement("div");
  banner.id = "schedule-completion-banner";
  banner.className =
    "mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg shadow-sm";
  banner.innerHTML = `
    <div class="flex items-start">
      <i class="fas fa-exclamation-triangle text-yellow-600 text-xl flex-shrink-0"></i>
      <div class="ml-3 flex-1">
        <h3 class="text-sm font-semibold text-yellow-800">Schedule Generation Incomplete</h3>
        <p class="text-sm text-yellow-700 mt-1">
          ${data.unassignedCourses.length} course(s) could not be scheduled.
          Success rate: ${data.successRate || "0%"}.
        </p>
        <button onclick="window.switchTab('manual')"
                class="mt-2 text-sm font-medium text-yellow-800 hover:underline">
          Go to Manual Edit →
        </button>
      </div>
      <button onclick="this.closest('#schedule-completion-banner').remove()"
              class="ml-3 text-yellow-600 hover:text-yellow-800">
        <i class="fas fa-times"></i>
      </button>
    </div>`;
  navTabs.parentElement.insertBefore(banner, navTabs.nextSibling);
}

// ============================================
// GLOBAL EXPORTS
// ============================================
window.generateSchedules = generateSchedules;
window.updateCourses = updateCourses;
window.updateUIAfterGeneration = updateUIAfterGeneration;
window.showRegenerateModal = showRegenerateModal;
window.closeRegenerateModal = closeRegenerateModal;
window.confirmRegenerate = confirmRegenerate;
window.checkExistingSchedulesBeforeGenerate =
  checkExistingSchedulesBeforeGenerate;
window.showIncompleteScheduleBanner = showIncompleteScheduleBanner;
window.updateScheduleCompletionStatus = updateScheduleCompletionStatus;
window.getFilteredSchedules = getFilteredSchedules;

// ============================================
// INIT
// ============================================
document.addEventListener("DOMContentLoaded", () => {
  initializeScheduleData();

  const curriculumSelect = document.getElementById("curriculum_id");
  if (curriculumSelect)
    curriculumSelect.addEventListener("change", updateCourses);

  const generateBtn = document.getElementById("generate-btn");
  if (generateBtn) {
    // Clone to remove any stale listeners before adding ours
    const fresh = generateBtn.cloneNode(true);
    generateBtn.parentNode.replaceChild(fresh, generateBtn);
    fresh.addEventListener("click", generateSchedules);
  }

  // Close regenerate modal on backdrop click or ESC
  const regModal = document.getElementById("regenerate-confirmation-modal");
  if (regModal) {
    regModal.addEventListener("click", (e) => {
      if (e.target === regModal) closeRegenerateModal();
    });
  }
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeRegenerateModal();
  });
});
